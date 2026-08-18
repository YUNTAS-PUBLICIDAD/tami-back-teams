<?php

namespace App\Http\Controllers\Api\V1\HomePopup;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomePopup\UpdateHomePopupSettingRequest;
use App\Models\HomePopupSetting;
use App\Models\PopupEmailStep;
use App\Models\PopupWhatsappStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HomePopupSettingController extends Controller
{
    public function showAdmin(): JsonResponse
    {
        $setting = $this->getOrCreateSettings()->load(['whatsappSteps', 'emailSteps']);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatResponse($setting),
        ]);
    }

    public function update(UpdateHomePopupSettingRequest $request): JsonResponse
    {
        $setting = $this->getOrCreateSettings();

        $data = [];

        foreach (['enabled', 'whatsapp_enabled', 'email_enabled'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->boolean($field);
            }
        }

        $textMapping = [
            'button_text' => 'button_text',
            'buttonText' => 'button_text',
            'button_bg_color' => 'button_bg_color',
            'btnBgColor' => 'button_bg_color',
            'button_text_color' => 'button_text_color',
            'btnTextColor' => 'button_text_color',
            'popup_start_delay_seconds' => 'popup_start_delay_seconds',
            'popupInicioDelay' => 'popup_start_delay_seconds',
            'popup_start_delay_minutes' => 'popup_start_delay_seconds',
        ];

        foreach ($textMapping as $frontKey => $dbColumn) {
            if ($request->has($frontKey)) {
                $data[$dbColumn] = $request->input($frontKey);
            }
        }

        // Imágenes del popup (escritorio y móvil)
        $imageFields = [
            'image1' => 'popup_image_url',
            'image2' => 'popup_image_2_url',
            'imageMobile' => 'popup_mobile_image_url',
            'imageMobile2' => 'popup_mobile_image2_url',
        ];

        foreach ($imageFields as $fileInput => $column) {
            if ($request->hasFile($fileInput)) {
                $data[$column] = $this->replaceImage($request->file($fileInput), $setting->$column);
            } elseif ($request->boolean('delete_' . $fileInput)) {
                if (!empty($setting->$column)) {
                    $this->deleteImage($setting->$column);
                }
                $data[$column] = null;
            }
        }

        $data['updated_by'] = Auth::id();

        $setting->update($data);

        $this->syncWhatsAppSteps($setting, $request);
        $this->syncEmailSteps($setting, $request);

        $setting->refresh()->load(['whatsappSteps', 'emailSteps']);

        return response()->json([
            'status' => 'success',
            'message' => 'Configuración de popup actualizada correctamente.',
            'data' => $this->formatResponse($setting),
        ]);
    }

    public function showPublic(): JsonResponse
    {
        $setting = $this->getOrCreateSettings()->load(['whatsappSteps', 'emailSteps']);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatResponse($setting),
        ]);
    }

    private function getOrCreateSettings(): HomePopupSetting
    {
        $setting = HomePopupSetting::firstOrCreate([], [
            'enabled' => true,
            'popup_start_delay_seconds' => 60,
            'button_text' => '!REGISTRARME!',
            'button_bg_color' => '#00AFA0',
            'button_text_color' => '#FFFFFF',
            'whatsapp_enabled' => false,
            'email_enabled' => false,
        ]);

        if ($setting->wasRecentlyCreated) {
            $emailDelays = [0, 30, 1440];

            for ($i = 1; $i <= 3; $i++) {
                PopupWhatsappStep::create([
                    'popup_setting_id' => $setting->id,
                    'sequence' => $i,
                    'delay_minutes' => 0,
                ]);

                PopupEmailStep::create([
                    'popup_setting_id' => $setting->id,
                    'sequence' => $i,
                    'delay_minutes' => $emailDelays[$i - 1],
                ]);
            }
        }

        return $setting;
    }

    private function syncWhatsAppSteps(HomePopupSetting $setting, Request $request): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $suffix = $i === 1 ? '' : (string) $i;
            $underscore = $i === 1 ? '' : "_{$i}";

            $step = $setting->whatsappSteps()->firstOrNew(['sequence' => $i]);
            $changed = $step->exists;

            $message = $this->firstPresent($request, ["whatsappMessage{$suffix}", "whatsapp_message{$underscore}"]);
            if ($message !== null) {
                $step->message = $message;
                $changed = true;
            }

            $time = $this->firstPresent($request, ["whatsappTime{$i}", "whatsapp_time_{$i}"]);
            if ($time !== null) {
                $step->delay_minutes = (int) $time;
                $changed = true;
            }

            $imgKey = "whatsappImage{$suffix}";

            if ($request->hasFile($imgKey)) {
                $step->image_url = $this->replaceImage($request->file($imgKey), $step->image_url);
                $changed = true;
            } elseif ($request->boolean('delete_' . $imgKey)) {
                if (!empty($step->image_url)) {
                    $this->deleteImage($step->image_url);
                }
                $step->image_url = null;
                $changed = true;
            }

            if ($changed || !empty($step->message) || !empty($step->image_url)) {
                $step->popup_setting_id = $setting->id;
                $step->save();
            }
        }
    }

    private function syncEmailSteps(HomePopupSetting $setting, Request $request): void
    {
        // Estos campos no deben sobrescribirse con vacío (conservan contenido previo)
        $emailContentFields = ['subject', 'message', 'btn_text', 'btn_link'];

        for ($i = 1; $i <= 3; $i++) {
            $suffix = $i === 1 ? '' : (string) $i;
            $underscore = $i === 1 ? '' : "_{$i}";

            $step = $setting->emailSteps()->firstOrNew(['sequence' => $i]);
            $changed = $step->exists;

            $fields = [
                'subject' => ["emailTitle{$suffix}", "email_subject{$underscore}"],
                'message' => ["emailBody{$suffix}", "email_message{$underscore}"],
                'btn_text' => ["email_btn_text{$underscore}", "emailBtnText{$suffix}"],
                'btn_link' => ["email_btn_link{$underscore}", "emailBtnLink{$suffix}"],
                'btn_bg_color' => ["email_btn_bg_color{$underscore}", "emailBtnBgColor{$suffix}"],
                'btn_text_color' => ["email_btn_text_color{$underscore}", "emailBtnTextColor{$suffix}"],
                'delay_minutes' => ["email_send_delay_minutes{$underscore}", "emailSendDelay{$suffix}"],
            ];

            foreach ($fields as $column => $keys) {
                $value = $this->firstPresent($request, $keys);
                if ($value === null) {
                    continue;
                }

                if (in_array($column, $emailContentFields, true) && trim((string) $value) === '') {
                    continue;
                }

                $step->{$column} = $column === 'delay_minutes' ? (int) $value : $value;
                $changed = true;
            }

            $imgKey = "emailImage{$suffix}";

            if ($request->hasFile($imgKey)) {
                $step->image_url = $this->replaceImage($request->file($imgKey), $step->image_url);
                $changed = true;
            } elseif ($request->boolean('delete_' . $imgKey)) {
                if (!empty($step->image_url)) {
                    $this->deleteImage($step->image_url);
                }
                $step->image_url = null;
                $changed = true;
            }

            $hasContent = !empty($step->subject) || !empty($step->message) || !empty($step->image_url);

            if ($changed || $hasContent) {
                $step->popup_setting_id = $setting->id;
                $step->save();
            }
        }
    }

    private function firstPresent(Request $request, array $keys): mixed
    {
        foreach ($keys as $key) {
            if ($request->has($key)) {
                return $request->input($key);
            }
        }

        return null;
    }

    private function replaceImage(UploadedFile $file, ?string $oldPublicUrl): string
    {
        $this->deleteImage($oldPublicUrl);

        $storedPath = $file->store('home-popup', 'public');

        return '/storage/' . $storedPath;
    }

    private function deleteImage(?string $publicUrl): void
    {
        if (!empty($publicUrl)) {
            $oldPath = str_replace('/storage/', '', $publicUrl);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
    }

    private function formatResponse(HomePopupSetting $setting): array
    {
        $data = $setting->toArray();

        // URLs absolutas para las imágenes del popup
        $data['popup_image_url'] = $this->absoluteUrl($data['popup_image_url'] ?? null);
        $data['popup_image_2_url'] = $this->absoluteUrl($data['popup_image_2_url'] ?? null);
        $data['popup_mobile_image_url'] = $this->absoluteUrl($data['popup_mobile_image_url'] ?? null);
        $data['popup_mobile_image2_url'] = $this->absoluteUrl($data['popup_mobile_image2_url'] ?? null);

        // Aliases para el Frontend
        $data['image1'] = $data['popup_image_url'];
        $data['image2'] = $data['popup_image_2_url'];
        $data['imageMobile'] = $data['popup_mobile_image_url'];
        $data['imageMobile2'] = $data['popup_mobile_image2_url'];

        $data['btnTextColor'] = $setting->button_text_color;
        $data['btnBgColor'] = $setting->button_bg_color;
        $data['buttonText'] = $setting->button_text;

        $data['popupInicioDelay'] = $setting->popup_start_delay_seconds;
        $data['popup_start_delay_minutes'] = $setting->popup_start_delay_seconds;

        // Pasos de WhatsApp (derivados de la tabla hija)
        $whatsappSteps = $setting->whatsappSteps->keyBy('sequence');

        for ($i = 1; $i <= 3; $i++) {
            $suffix = $i === 1 ? '' : (string) $i;
            $step = $whatsappSteps[$i] ?? null;

            $data["whatsappMessage{$suffix}"] = $step->message ?? null;
            $data["whatsappTime{$i}"] = $step->delay_minutes ?? 0;
            $data["whatsappImage{$suffix}"] = $this->absoluteUrl($step->image_url ?? null);
        }

        // Pasos de Email (derivados de la tabla hija)
        $emailSteps = $setting->emailSteps->keyBy('sequence');

        for ($i = 1; $i <= 3; $i++) {
            $suffix = $i === 1 ? '' : (string) $i;
            $underscore = $i === 1 ? '' : "_{$i}";
            $step = $emailSteps[$i] ?? null;

            $data["emailTitle{$suffix}"] = $step->subject ?? null;
            $data["emailBody{$suffix}"] = $step->message ?? null;
            $data["emailImage{$suffix}"] = $this->absoluteUrl($step->image_url ?? null);
            $data["email_btn_text{$underscore}"] = $step->btn_text ?? null;
            $data["email_btn_link{$underscore}"] = $step->btn_link ?? null;
            $data["email_btn_bg_color{$underscore}"] = $step->btn_bg_color ?? null;
            $data["email_btn_text_color{$underscore}"] = $step->btn_text_color ?? null;
            $data["emailSendDelay{$suffix}"] = $step->delay_minutes ?? 0;
            $data["email_send_delay_minutes{$underscore}"] = $step->delay_minutes ?? 0;
        }

        return $data;
    }

    private function absoluteUrl(?string $path): ?string
    {
        return $path ? url($path) : null;
    }
}