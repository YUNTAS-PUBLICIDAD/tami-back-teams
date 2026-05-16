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
    public $customMailData;

    /**
     * Create a new job instance.
     */
    public function __construct(Cliente $cliente, int $emailNumber, array $customMailData = [])
    {
        $this->cliente = $cliente;
        $this->emailNumber = $emailNumber;
        $this->customMailData = $customMailData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $setting = HomePopupSetting::first();
            if (!$setting && empty($this->customMailData)) {
                return;
            }
            if (empty($this->customMailData) && !$setting->email_enabled) {
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

            $subject = $this->customMailData[$subjectField] ?? ($setting ? $setting->$subjectField : null);
            $message = $this->customMailData[$messageField] ?? ($setting ? $setting->$messageField : null);
            $imgUrl = $this->customMailData[$imageField] ?? ($setting ? $setting->$imageField : null);
            $btnText = $this->customMailData[$btnTextField] ?? ($setting ? $setting->$btnTextField : null);
            $btnLink = $this->customMailData[$btnLinkField] ?? ($setting ? $setting->$btnLinkField : null);
            $btnBgColor = $this->customMailData[$btnBgColorField] ?? ($setting ? $setting->$btnBgColorField : null);
            $btnTextColor = $this->customMailData[$btnTextColorField] ?? ($setting ? $setting->$btnTextColorField : null);

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
                'image_url' => $imgUrl ? url($imgUrl) : null,
                'email_btn_text' => $btnText ?: '¡REGISTRARME!',
                'email_btn_link' => $btnLink ?: url('/'),
                'email_btn_bg_color' => $btnBgColor ?: '#00AFA0',
                'email_btn_text_color' => $btnTextColor ?: '#FFFFFF',
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
