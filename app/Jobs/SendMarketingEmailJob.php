<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\HomePopupSetting;
use App\Mail\ClientRegistrationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendMarketingEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $cliente;
    public $emailNumber;
    public $emailData;

    /**
     * Create a new job instance.
     *
     * @param array $emailData Datos normalizados del paso de correo:
     *                         ['subject', 'message', 'image_url', 'btn_text', 'btn_link', 'btn_bg_color', 'btn_text_color']
     */
    public function __construct(Cliente $cliente, int $emailNumber, array $emailData = [])
    {
        $this->cliente = $cliente;
        $this->emailNumber = $emailNumber;
        $this->emailData = $emailData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $data = $this->emailData;

            if (empty($data)) {
                $setting = HomePopupSetting::with(['emailSteps'])->first();
                if (!$setting || !$setting->email_enabled) {
                    return;
                }

                $step = $setting->emailSteps->firstWhere('sequence', $this->emailNumber);
                if (!$step) {
                    return;
                }

                $data = [
                    'subject' => $step->subject,
                    'message' => $step->message,
                    'image_url' => $step->image_url,
                    'btn_text' => $step->btn_text,
                    'btn_link' => $step->btn_link,
                    'btn_bg_color' => $step->btn_bg_color,
                    'btn_text_color' => $step->btn_text_color,
                ];
            }

            $subject = $data['subject'] ?? null;
            $message = $data['message'] ?? null;

            if (empty($subject) || empty($message)) {
                Log::info("SendMarketingEmailJob: No content for Email #{$this->emailNumber}. Skipping.");
                return;
            }

            // Personalización
            $message = str_replace('{{nombre}}', $this->cliente->name, $message);

            $imgUrl = $data['image_url'] ?? null;

            $mailData = [
                'name'    => $this->cliente->name,
                'email'   => $this->cliente->email,
                'celular' => $this->cliente->celular,
                'subject' => $subject,
                'message' => $message,
                'image_url' => $imgUrl ? url($imgUrl) : null,
                'email_btn_text' => $data['btn_text'] ?: '¡REGISTRARME!',
                'email_btn_link' => $data['btn_link'] ?: url('/'),
                'email_btn_bg_color' => $data['btn_bg_color'] ?: '#00AFA0',
                'email_btn_text_color' => $data['btn_text_color'] ?: '#FFFFFF',
            ];

            // Ruta absoluta para embeber la imagen
            if ($imgUrl) {
                $filePath = public_path($imgUrl);
                if (file_exists($filePath)) {
                    $mailData['image_path'] = $filePath;
                }
            }

            Mail::to($this->cliente->email)->send(new ClientRegistrationMail($mailData));

            Log::info("SendMarketingEmailJob: Email #{$this->emailNumber} sent to {$this->cliente->email}");

        } catch (\Exception $e) {
            Log::error("Error in SendMarketingEmailJob (Email #{$this->emailNumber}): " . $e->getMessage());
        }
    }
}