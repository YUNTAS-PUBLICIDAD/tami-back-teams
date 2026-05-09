<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsappMessageLog;

class SendWhatsAppPopUpMessageJob implements ShouldQueue
{
    use Queueable;
    use \App\Traits\FormatsTextTrait;

    public $cliente;
    public $messageRaw;
    public $imageUrl;
    public $requestData;

    /**
     * Create a new job instance.
     */
    public function __construct($cliente, $messageRaw, $imageUrl, $requestData)
    {
        $this->cliente = $cliente;
        $this->messageRaw = $messageRaw;
        $this->imageUrl = $imageUrl;
        $this->requestData = $requestData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $whatsappServiceUrl = config('services.whatsapp.base_url');
            if (!$whatsappServiceUrl || empty($this->messageRaw)) {
                return;
            }

            $url = $whatsappServiceUrl . '/whatsapp/send-campaign';
            
            // Si el imageUrl es relativo, convertir a absoluto
            $fullImageUrl = $this->imageUrl;
            if ($fullImageUrl && !filter_var($fullImageUrl, FILTER_VALIDATE_URL)) {
                $fullImageUrl = url($fullImageUrl);
            }

            $messageRaw = $this->messageRaw;
            $nombreCliente = $this->requestData['name'] ?? ($this->cliente->name !== 'Cliente Popup' ? $this->cliente->name : null);
            
            if ($nombreCliente) {
                // Solo agregamos el saludo si no parece tener uno ya
                if (stripos($messageRaw, '¡Hola') === false && stripos($messageRaw, 'Hola') === false) {
                    $messageRaw = "¡Hola {$nombreCliente}!.\n\n" . $messageRaw;
                }
            }

            $message = $this->formatHtmlForWhatsapp($messageRaw);

            $payload = [
                'phone'   => $this->requestData['celular'],
                'message' => $message,
                'image'   => $fullImageUrl,
            ];

            $response = Http::timeout(10)->post($url, $payload);

            if ($response->successful()) {
                WhatsappMessageLog::create([
                    'cliente_id' => $this->cliente->id,
                    'phone' => $this->requestData['celular'],
                    'email' => $this->requestData['email'] ?? null,
                    'status' => 'success',
                    'image_url' => $fullImageUrl,
                ]);
            } else {
                Log::error('Error respuesta servicio WhatsApp: ' . $response->body());
                $this->logFailure($fullImageUrl, $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('Error en SendWhatsAppPopUpMessageJob: ' . $e->getMessage());
            $this->logFailure($this->imageUrl ?? null, $e->getMessage());
        }
    }

    private function logFailure($imageUrl, $error)
    {
        WhatsappMessageLog::create([
            'cliente_id' => $this->cliente->id,
            'phone' => $this->requestData['celular'],
            'email' => $this->requestData['email'] ?? null,
            'status' => 'failed',
            'error_message' => $error,
            'image_url' => $imageUrl,
        ]);
    }
}
