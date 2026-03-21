<?php

namespace App\Http\Controllers\Api\V1\HomePopup;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomePopup\UpdateHomePopupSettingRequest;
use App\Models\HomePopupSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HomePopupSettingController extends Controller
{
    public function showAdmin(): JsonResponse
    {
        $setting = $this->getOrCreateSettings();
        
        // Convert URLs to absolute
        $imageFields = [
            'popup_image_url', 'popup_image2_url', 'popup_mobile_image_url', 
            'whatsapp_image_url', 'email_image_url'
        ];
        
        foreach ($imageFields as $field) {
            if ($setting->$field) {
                $setting->$field = url($setting->$field);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $setting,
        ]);
    }

    public function update(UpdateHomePopupSettingRequest $request): JsonResponse
    {
        $setting = $this->getOrCreateSettings();
        $validated = $request->validated();
        
        $data = [];

        // Direct standard fields
        $directFields = ['enabled', 'title', 'subtitle', 'button_text', 'whatsapp_enabled', 'email_enabled'];
        foreach ($directFields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        // Custom frontend keys mapped to DB Columns
        $textMapping = [
            'btnTextColor' => 'button_text_color',
            'btnBgColor' => 'button_bg_color',
            'popupInicioDelay' => 'popup_start_delay_minutes',
            'popupProductosDelay' => 'product_popup_delay_minutes',
            'whatsappMessage' => 'whatsapp_message',
            'emailTitle' => 'email_subject',
            'emailBody' => 'email_message',
        ];

        foreach ($textMapping as $frontKey => $dbColumn) {
            if ($request->has($frontKey)) {
                $data[$dbColumn] = $request->input($frontKey);
            }
        }

        $imageFields = [
            'image1' => 'popup_image_url',
            'image2' => 'popup_image2_url',
            'imageMobile' => 'popup_mobile_image_url',
            'whatsappImage' => 'whatsapp_image_url',
            'emailImage' => 'email_image_url',
        ];

        // Procesar subida de nuevas imágenes
        foreach ($imageFields as $fileInput => $dbColumn) {
            if ($request->hasFile($fileInput)) {
                $data[$dbColumn] = $this->replaceImage(
                    $request->file($fileInput),
                    $setting->$dbColumn
                );
            } elseif ($request->boolean('delete_' . $fileInput)) {
                // Borrar si se envió el flag de borrado y no se subió una imagen nueva
                if (!empty($setting->$dbColumn)) {
                    $this->deleteImage($setting->$dbColumn);
                }
                $data[$dbColumn] = null;
            }
        }

        $data['updated_by'] = Auth::id();

        $setting->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Configuración de popup de inicio actualizada correctamente.',
            'data' => $setting->fresh(),
        ]);
    }

    public function showPublic(): JsonResponse
    {
        $setting = $this->getOrCreateSettings();

        return response()->json([
            'status' => 'success',
            'data' => [
                'enabled' => $setting->enabled,
                'popup_start_delay_minutes' => $setting->popup_start_delay_minutes,
                'product_popup_delay_minutes' => $setting->product_popup_delay_minutes,
                'title' => $setting->title,
                'subtitle' => $setting->subtitle,
                'popup_image_url' => $setting->popup_image_url ? url($setting->popup_image_url) : null,
                'popup_image2_url' => $setting->popup_image2_url ? url($setting->popup_image2_url) : null,
                'popup_mobile_image_url' => $setting->popup_mobile_image_url ? url($setting->popup_mobile_image_url) : null,
                'button_text' => $setting->button_text,
                'button_bg_color' => $setting->button_bg_color,
                'button_text_color' => $setting->button_text_color,
                'whatsapp_enabled' => $setting->whatsapp_enabled,
                'whatsapp_message' => $setting->whatsapp_message,
                'whatsapp_image_url' => $setting->whatsapp_image_url ? url($setting->whatsapp_image_url) : null,
                'email_enabled' => $setting->email_enabled,
                'email_subject' => $setting->email_subject,
                'email_message' => $setting->email_message,
                'email_image_url' => $setting->email_image_url ? url($setting->email_image_url) : null,
            ],
        ]);
    }

    private function getOrCreateSettings(): HomePopupSetting
    {
        return HomePopupSetting::firstOrCreate([], [
            'enabled' => false,
            'popup_start_delay_minutes' => 1,
            'product_popup_delay_minutes' => 1,
            'button_text' => '!REGISTRARME!',
            'button_bg_color' => '#00AFA0',
            'button_text_color' => '#FFFFFF',
            'whatsapp_enabled' => false,
            'email_enabled' => false,
        ]);
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
}
