<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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

        // --- LÓGICA DE CORREO ---
        if (!empty($requestData['email'])) {
            try {
                $mailData = [
                    'name'    => $requestData['name'],
                    'email'   => $requestData['email'],
                    'celular' => $requestData['celular'],
                    'subject' => $setting->email_subject,
                    'message' => $setting->email_message,
                    'image_url' => $setting->email_image_url ? url($setting->email_image_url) : null,
                    'image_path' => $setting->email_image_url ? public_path($setting->email_image_url) : null,
                    // Nuevos campos del botón
                    'email_btn_text' => $setting->email_btn_text ?: '¡REGISTRARME!',
                    'email_btn_link' => $setting->email_btn_link ?: url('/'),
                    'email_btn_bg_color' => $setting->email_btn_bg_color ?: '#00AFA0',
                    'email_btn_text_color' => $setting->email_btn_text_color ?: '#FFFFFF',
                ];

                \Illuminate\Support\Facades\Mail::to($requestData['email'])->send(new \App\Mail\ClientRegistrationMail($mailData));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error en Job Correo: ' . $e->getMessage());
            }
        }
    }
}
