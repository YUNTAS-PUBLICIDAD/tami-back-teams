<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Jobs\SendMarketingEmailJob;

class ProcessPopUpSubmissionJob implements ShouldQueue
{
    use Queueable;

    public $cliente;
    public $setting;
    public $requestData;

    /**
     * Create a new job instance.
     */
    public function __construct($cliente, $setting, $requestData)
    {
        $this->cliente = $cliente;
        $this->setting = $setting;
        $this->requestData = $requestData;
    }

    use \App\Traits\FormatsTextTrait;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cliente = $this->cliente;
        $setting = $this->setting;
        $requestData = $this->requestData;

        // --- LÓGICA DE WHATSAPP ---
        try {
            $whatsappServiceUrl = config('services.whatsapp.base_url');
            if ($whatsappServiceUrl && !empty($setting->whatsapp_message)) {
                $url = $whatsappServiceUrl . '/whatsapp/send-campaign';
                $imageUrl = $setting->whatsapp_image_url ? url($setting->whatsapp_image_url) : null;
                $messageRaw = $setting->whatsapp_message;

                $nombreCliente = $requestData['name'] ?? ($cliente->name !== 'Cliente Popup' ? $cliente->name : null);
                if ($nombreCliente) {
                    $messageRaw = "¡Hola {$nombreCliente}! Bienvenido/a.\n\n" . $messageRaw;
                }

                $message = $this->formatHtmlForWhatsapp($messageRaw);

                $payload = [
                    'phone'   => $requestData['celular'],
                    'message' => $message,
                    'image'   => $imageUrl,
                ];

                \Illuminate\Support\Facades\Http::timeout(10)->post($url, $payload);

                \App\Models\WhatsappMessageLog::create([
                    'cliente_id' => $cliente->id,
                    'phone' => $requestData['celular'],
                    'email' => $requestData['email'],
                    'status' => 'success',
                    'image_url' => $imageUrl,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error en Job WhatsApp: ' . $e->getMessage());
        }

        // --- LÓGICA DE CORREO SECUENCIAL ---
        if (!empty($requestData['email']) && $setting->email_enabled) {
            try {
                // Email 1
                $delay1 = $setting->email_send_delay_minutes !== null ? (int) $setting->email_send_delay_minutes : 0;
                if ($delay1 !== -1) {
                    SendMarketingEmailJob::dispatch($cliente, 1)->delay(now()->addMinutes($delay1));
                }

                // Email 2
                $delay2 = $setting->email_send_delay_minutes_2 !== null ? (int) $setting->email_send_delay_minutes_2 : 30;
                if ($delay2 !== -1) {
                    SendMarketingEmailJob::dispatch($cliente, 2)->delay(now()->addMinutes($delay2));
                }

                // Email 3
                $delay3 = $setting->email_send_delay_minutes_3 !== null ? (int) $setting->email_send_delay_minutes_3 : 1440;
                if ($delay3 !== -1) {
                    SendMarketingEmailJob::dispatch($cliente, 3)->delay(now()->addMinutes($delay3));
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error al programar correos secuenciales: ' . $e->getMessage());
            }
        }
    }
}
