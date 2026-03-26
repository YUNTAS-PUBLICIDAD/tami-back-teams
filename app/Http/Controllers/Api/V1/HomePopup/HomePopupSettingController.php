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
            'popup_mobile_image2_url', 'whatsapp_image_url', 'email_image_url'
        ];
        
        foreach ($imageFields as $field) {
            if ($setting->$field) {
                $setting->$field = url($setting->$field);
            }
        }

        // Mapear para que el frontend (React/Vue) reciba las mismas llaves que envía
        $responseData = $setting->toArray();
        $responseData['btnTextColor'] = $setting->button_text_color;
        $responseData['btnBgColor'] = $setting->button_bg_color;
        $responseData['popupInicioDelay'] = $setting->popup_start_delay_minutes;
        $responseData['popupProductosDelay'] = $setting->product_popup_delay_minutes;
        $responseData['whatsappMessage'] = $setting->whatsapp_message;
        $responseData['emailTitle'] = $setting->email_subject;
        $responseData['emailBody'] = $setting->email_message;
        
        // Alias para imágenes
        $responseData['whatsappImage'] = $setting->whatsapp_image_url;
        $responseData['emailImage'] = $setting->email_image_url;
        $responseData['image1'] = $setting->popup_image_url;
        $responseData['image2'] = $setting->popup_image2_url;
        $responseData['imageMobile'] = $setting->popup_mobile_image_url;

        return response()->json([
            'status' => 'success',
            'data' => $responseData,
        ]);
    }

    public function update(UpdateHomePopupSettingRequest $request): JsonResponse
    {
        $setting = $this->getOrCreateSettings();
        
        // Log para depuración
        \Log::info('Petición de actualización de popup recibida', $request->all());

        $data = [];

        // Campos booleanos
        $booleans = [
            'enabled', 'whatsapp_enabled', 'email_enabled',
            'whatsapp_enabled' => 'whatsapp_enabled',
        ];
        
        foreach ($booleans as $key => $column) {
            $searchKey = is_int($key) ? $column : $key;
            if ($request->has($searchKey)) {
                $data[$column] = $request->boolean($searchKey);
            }
        }

        // Mapeo de campos de texto (Soporta camelCase y snake_case)
        $textMapping = [
            'title' => 'title',
            'subtitle' => 'subtitle',
            'button_text' => 'button_text',
            'buttonText' => 'button_text',
            'button_bg_color' => 'button_bg_color',
            'btnBgColor' => 'button_bg_color',
            'button_text_color' => 'button_text_color',
            'btnTextColor' => 'button_text_color',
            'popup_start_delay_minutes' => 'popup_start_delay_minutes',
            'popupInicioDelay' => 'popup_start_delay_minutes',
            'product_popup_delay_minutes' => 'product_popup_delay_minutes',
            'popupProductosDelay' => 'product_popup_delay_minutes',
            'whatsapp_message' => 'whatsapp_message',
            'whatsappMessage' => 'whatsapp_message',
            'email_subject' => 'email_subject',
            'emailTitle' => 'email_subject',
            'email_message' => 'email_message',
            'emailBody' => 'email_message',
        ];

        foreach ($textMapping as $frontKey => $dbColumn) {
            if ($request->has($frontKey)) {
                $data[$dbColumn] = $request->input($frontKey);
            }
        }

        // Mapeo de imágenes
        $imageFields = [
            'image1' => 'popup_image_url',
            'popup_image' => 'popup_image_url',
            'image2' => 'popup_image2_url',
            'popup_image_2' => 'popup_image2_url',
            'imageMobile' => 'popup_mobile_image_url',
            'imageMobile2' => 'popup_mobile_image2_url',
            'popup_mobile_image' => 'popup_mobile_image_url',
            'whatsappImage' => 'whatsapp_image_url',
            'whatsapp_image' => 'whatsapp_image_url',
            'emailImage' => 'email_image_url',
            'email_image' => 'email_image_url',
        ];

        foreach ($imageFields as $fileInput => $dbColumn) {
            if ($request->hasFile($fileInput)) {
                $data[$dbColumn] = $this->replaceImage(
                    $request->file($fileInput),
                    $setting->$dbColumn
                );
            } elseif ($request->boolean('delete_' . $fileInput)) {
                if (!empty($setting->$dbColumn)) {
                    $this->deleteImage($setting->$dbColumn);
                }
                $data[$dbColumn] = null;
            }
        }

        $data['updated_by'] = Auth::id();

        \Log::info('Datos a actualizar en BD', $data);

        $setting->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Configuración de popup actualizada correctamente.',
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
                'popupInicioDelay' => $setting->popup_start_delay_minutes,
                'popupProductosDelay' => $setting->product_popup_delay_minutes,
                'title' => $setting->title,
                'subtitle' => $setting->subtitle,
                'popup_image_url' => $setting->popup_image_url,
                'popup_image_2_url' => $setting->popup_image_2_url,
                'popup_mobile_image_url' => $setting->popup_mobile_image_url,
                'popup_mobile_image2_url' => $setting->popup_mobile_image2_url,
                'popup_image_url' => $setting->popup_image_url ? url($setting->popup_image_url) : null,
                'popup_image_2_url' => $setting->popup_image_2_url ? url($setting->popup_image_2_url) : null,
                'button_text' => $setting->button_text,
                'btnBgColor' => $setting->button_bg_color,
                'btnTextColor' => $setting->button_text_color,
                'whatsapp_enabled' => $setting->whatsapp_enabled,
                'whatsappMessage' => $setting->whatsapp_message,
                'whatsapp_image_url' => $setting->whatsapp_image_url ? url($setting->whatsapp_image_url) : null,
                'whatsappImage' => $setting->whatsapp_image_url ? url($setting->whatsapp_image_url) : null,
                'email_enabled' => $setting->email_enabled,
                'emailTitle' => $setting->email_subject,
                'emailBody' => $setting->email_message,
                'emailImage' => $setting->email_image_url ? url($setting->email_image_url) : null,
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
