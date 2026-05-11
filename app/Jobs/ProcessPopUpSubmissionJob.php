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
            if ($whatsappServiceUrl) {
                $messages = [
                    ['text' => $setting->whatsapp_message, 'image' => $setting->whatsapp_image_url, 'time' => $setting->whatsapp_time_1 ?? 0],
                    ['text' => $setting->whatsapp_message_2 ?? null, 'image' => $setting->whatsapp_image_url_2 ?? null, 'time' => $setting->whatsapp_time_2 ?? 0],
                    ['text' => $setting->whatsapp_message_3 ?? null, 'image' => $setting->whatsapp_image_url_3 ?? null, 'time' => $setting->whatsapp_time_3 ?? 0],
                ];

                $cumulativeDelay = 0;

                foreach ($messages as $msgData) {
                    if (!empty($msgData['text'])) {
                        $cumulativeDelay += (int)$msgData['time'];
                        
                        $job = new \App\Jobs\SendWhatsAppPopUpMessageJob(
                            $cliente,
                            $msgData['text'],
                            $msgData['image'],
                            $requestData
                        );

                        if ($cumulativeDelay > 0) {
                            $job->delay(now()->addMinutes($cumulativeDelay));
                        } else {
                            $job->delay(now()->addSeconds(10));
                        }

                        dispatch($job);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error en Job WhatsApp (scheduling): ' . $e->getMessage());
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
