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
    public $popupType;

    /**
     * Create a new job instance.
     */
    public function __construct($cliente, $setting, $requestData, $popupType = 'inicio')
    {
        $this->cliente = $cliente;
        $this->setting = $setting;
        $this->requestData = $requestData;
        $this->popupType = $popupType;
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
        $popupType = $this->popupType ?? 'inicio';

        // --- LÓGICA DE WHATSAPP ---
        try {
            $whatsappServiceUrl = config('services.whatsapp.base_url');
            if ($whatsappServiceUrl) {
                // Obtener los campos correctos según el tipo de popup
                if ($popupType === 'producto') {
                    $msg1 = $setting->whatsapp_message_producto ?? $setting->whatsapp_message ?? null;
                    $img1 = $setting->whatsapp_image_url_producto ?? $setting->whatsapp_image_url ?? null;
                    $time1 = $setting->whatsapp_time_1_producto ?? $setting->whatsapp_time_1 ?? 0;
                    $msg2 = $setting->whatsapp_message_2_producto ?? $setting->whatsapp_message_2 ?? null;
                    $img2 = $setting->whatsapp_image_url_2_producto ?? $setting->whatsapp_image_url_2 ?? null;
                    $time2 = $setting->whatsapp_time_2_producto ?? $setting->whatsapp_time_2 ?? 0;
                    $msg3 = $setting->whatsapp_message_3_producto ?? $setting->whatsapp_message_3 ?? null;
                    $img3 = $setting->whatsapp_image_url_3_producto ?? $setting->whatsapp_image_url_3 ?? null;
                    $time3 = $setting->whatsapp_time_3_producto ?? $setting->whatsapp_time_3 ?? 0;
                } else {
                    // Por defecto 'inicio'
                    $msg1 = $setting->whatsapp_message_inicio ?? $setting->whatsapp_message ?? null;
                    $img1 = $setting->whatsapp_image_url_inicio ?? $setting->whatsapp_image_url ?? null;
                    $time1 = $setting->whatsapp_time_1_inicio ?? $setting->whatsapp_time_1 ?? 0;
                    $msg2 = $setting->whatsapp_message_2_inicio ?? $setting->whatsapp_message_2 ?? null;
                    $img2 = $setting->whatsapp_image_url_2_inicio ?? $setting->whatsapp_image_url_2 ?? null;
                    $time2 = $setting->whatsapp_time_2_inicio ?? $setting->whatsapp_time_2 ?? 0;
                    $msg3 = $setting->whatsapp_message_3_inicio ?? $setting->whatsapp_message_3 ?? null;
                    $img3 = $setting->whatsapp_image_url_3_inicio ?? $setting->whatsapp_image_url_3 ?? null;
                    $time3 = $setting->whatsapp_time_3_inicio ?? $setting->whatsapp_time_3 ?? 0;
                }

                $messages = [
                    ['text' => $msg1, 'image' => $img1, 'time' => $time1 ?? 0],
                    ['text' => $msg2 ?? null, 'image' => $img2 ?? null, 'time' => $time2 ?? 0],
                    ['text' => $msg3 ?? null, 'image' => $img3 ?? null, 'time' => $time3 ?? 0],
                ];

                $cumulativeDelay = 0;
                $messageIndex = 0;
                $minCooldownSeconds = 5; // Cooldown mínimo entre mensajes para que WhatsApp procese correctamente

                foreach ($messages as $msgData) {
                    if (!empty($msgData['text'])) {
                        $cumulativeDelay += (int)$msgData['time'];

                        // Convertir a segundos y agregar cooldown mínimo entre cada mensaje
                        $totalDelaySeconds = ($cumulativeDelay * 60) + ($messageIndex * $minCooldownSeconds);

                        $job = new \App\Jobs\SendWhatsAppPopUpMessageJob(
                            $cliente,
                            $msgData['text'],
                            $msgData['image'],
                            $requestData
                        );

                        if ($totalDelaySeconds > 0) {
                            $job->delay(now()->addSeconds($totalDelaySeconds));
                        }

                        dispatch($job);
                        $messageIndex++;
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
