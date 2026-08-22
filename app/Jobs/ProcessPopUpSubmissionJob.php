<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Jobs\SendMarketingEmailJob;
use App\Models\HomePopupSetting;

class ProcessPopUpSubmissionJob implements ShouldQueue
{
    use Queueable;

    public $cliente;
    public $setting;
    public $requestData;

    /**
     * Create a new job instance.
     *
     * $setting puede ser el modelo HomePopupSetting (popup global) o un stdClass
     * construido por WhatsAppController con las propiedades:
     *   whatsapp_steps: [{ message, image_url, delay_minutes }, ...]
     *   email_steps:    [{ subject, message, image_url, btn_text, btn_link, btn_bg_color, btn_text_color, delay_minutes }, ...]
     *   email_enabled:  bool
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
                $whatsappSteps = $this->resolveWhatsappSteps($setting);

                $messageIndex = 0;
                $minCooldownSeconds = 5; // Cooldown mínimo entre mensajes para que WhatsApp procese correctamente

                foreach ($whatsappSteps as $step) {
                    $timeValue = (int) ($step['delay_minutes'] ?? 0);

                    if (!empty($step['message']) && $timeValue !== -1) {
                        // Convertir a segundos y agregar cooldown mínimo entre cada mensaje
                        $totalDelaySeconds = ($timeValue * 60) + ($messageIndex * $minCooldownSeconds);

                        $job = new \App\Jobs\SendWhatsAppPopUpMessageJob(
                            $cliente,
                            $step['message'],
                            $step['image_url'] ?? null,
                            $requestData
                        );

                        if ($totalDelaySeconds > 0) {
                            $job->delay(now()->addSeconds($totalDelaySeconds));
                        }

                        dispatch($job)->onQueue('whatsapp');
                        $messageIndex++;
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error en Job WhatsApp (scheduling): ' . $e->getMessage());
        }

        // --- LÓGICA DE CORREO SECUENCIAL ---
        if (!empty($requestData['email']) && $this->isEmailEnabled($setting)) {
            try {
                $emailSteps = $this->resolveEmailSteps($setting);

                foreach ($emailSteps as $index => $step) {
                    $delay = (int) ($step['delay_minutes'] ?? 0);

                    if ($delay === -1) {
                        continue;
                    }

                    $time = now()->addMinutes($delay)->addSeconds($delay === 0 ? 5 : 0);

                    SendMarketingEmailJob::dispatch($cliente, $index + 1, $step)
                        ->onQueue('emails')
                        ->delay($time);
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error al programar correos secuenciales: ' . $e->getMessage());
            }
        }
    }

    private function resolveWhatsappSteps($setting): array
    {
        if ($setting instanceof HomePopupSetting) {
            return $setting->whatsappSteps
                ->map(fn ($s) => [
                    'message' => $s->message,
                    'image_url' => $s->image_url,
                    'delay_minutes' => $s->delay_minutes,
                ])
                ->values()
                ->all();
        }

        return collect($setting->whatsapp_steps ?? [])
            ->map(fn ($s) => (array) $s)
            ->values()
            ->all();
    }

    private function resolveEmailSteps($setting): array
    {
        if ($setting instanceof HomePopupSetting) {
            return $setting->emailSteps
                ->map(fn ($s) => [
                    'subject' => $s->subject,
                    'message' => $s->message,
                    'image_url' => $s->image_url,
                    'btn_text' => $s->btn_text,
                    'btn_link' => $s->btn_link,
                    'btn_bg_color' => $s->btn_bg_color,
                    'btn_text_color' => $s->btn_text_color,
                    'delay_minutes' => $s->delay_minutes,
                ])
                ->values()
                ->all();
        }

        return collect($setting->email_steps ?? [])
            ->map(fn ($s) => (array) $s)
            ->values()
            ->all();
    }

    private function isEmailEnabled($setting): bool
    {
        if ($setting instanceof HomePopupSetting) {
            return (bool) $setting->email_enabled;
        }

        return !empty($setting->email_enabled);
    }
}