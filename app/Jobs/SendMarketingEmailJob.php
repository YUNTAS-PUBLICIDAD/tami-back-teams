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

    /**
     * Create a new job instance.
     */
    public function __construct(Cliente $cliente, int $emailNumber)
    {
        $this->cliente = $cliente;
        $this->emailNumber = $emailNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $setting = HomePopupSetting::first();
            if (!$setting || !$setting->email_enabled) {
                return;
            }

            $suffix = $this->emailNumber === 1 ? '' : '_' . $this->emailNumber;
            
            $subjectField = 'email_subject' . $suffix;
            $messageField = 'email_message' . $suffix;
            $imageField = 'email_image_url' . $suffix;
            $btnTextField = 'email_btn_text' . $suffix;
            $btnLinkField = 'email_btn_link' . $suffix;
            $btnBgColorField = 'email_btn_bg_color' . $suffix;
            $btnTextColorField = 'email_btn_text_color' . $suffix;

            $subject = $setting->$subjectField;
            $message = $setting->$messageField;

            if (empty($subject) || empty($message)) {
                Log::info("SendMarketingEmailJob: No content for Email #{$this->emailNumber}. Skipping.");
                return;
            }

            // Personalización
            $message = str_replace('{{nombre}}', $this->cliente->name, $message);

            $mailData = [
                'name'    => $this->cliente->name,
                'email'   => $this->cliente->email,
                'celular' => $this->cliente->celular,
                'subject' => $subject,
                'message' => $message,
                'image_url' => $setting->$imageField ? url($setting->$imageField) : null,
                'email_btn_text' => $setting->$btnTextField ?: '¡REGISTRARME!',
                'email_btn_link' => $setting->$btnLinkField ?: url('/'),
                'email_btn_bg_color' => $setting->$btnBgColorField ?: '#00AFA0',
                'email_btn_text_color' => $setting->$btnTextColorField ?: '#FFFFFF',
            ];

            // Ruta absoluta para embeber la imagen
            if ($setting->$imageField) {
                $filePath = public_path($setting->$imageField);
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
