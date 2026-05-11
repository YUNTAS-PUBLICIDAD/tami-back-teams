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
            
            // Manejo de imagen (preferir base64 para evitar problemas de red)
            $imageData = null;
            if (!empty($this->imageUrl)) {
                if (filter_var($this->imageUrl, FILTER_VALIDATE_URL)) {
                    $imageData = $this->imageUrl;
                } else {
                    try {
                        $path = str_replace('/storage/', '', $this->imageUrl);
                        if (\Storage::disk('public')->exists($path)) {
                            $file = \Storage::disk('public')->get($path);
                            $mime = \Storage::disk('public')->mimeType($path);
                            $imageData = 'data:' . $mime . ';base64,' . base64_encode($file);
                        } else {
                            // Fallback a URL si no se encuentra el archivo localmente
                            $imageData = url($this->imageUrl);
                        }
                    } catch (\Throwable $th) {
                        Log::error('Error al convertir imagen a base64 en Job: ' . $th->getMessage());
                        $imageData = url($this->imageUrl);
                    }
                }
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
                'image'   => $imageData,
            ];

            Log::info('Enviando petición a WhatsApp:', [
                'url' => $url,
                'payload' => $payload
            ]);

            // Limpiar imageData para los logs (evitar guardar base64 gigante)
            $logImageUrl = $imageData;
            if (str_starts_with($logImageUrl ?? '', 'data:image/')) {
                $logImageUrl = '[Base64 Image]';
            }

            $response = Http::timeout(10)->post($url, $payload);

            if ($response->successful()) {
                WhatsappMessageLog::create([
                    'cliente_id' => $this->cliente->id,
                    'phone' => $this->requestData['celular'],
                    'email' => $this->requestData['email'] ?? null,
                    'status' => 'success',
                    'image_url' => $logImageUrl,
                ]);
            } else {
                Log::error('Error respuesta servicio WhatsApp: ' . $response->body());
                $this->logFailure($logImageUrl, $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('Error en SendWhatsAppPopUpMessageJob: ' . $e->getMessage());
            
            $errorImageUrl = $this->imageUrl;
            if (str_starts_with($errorImageUrl ?? '', 'data:image/')) {
                $errorImageUrl = '[Base64 Image]';
            }
            $this->logFailure($errorImageUrl, $e->getMessage());
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
