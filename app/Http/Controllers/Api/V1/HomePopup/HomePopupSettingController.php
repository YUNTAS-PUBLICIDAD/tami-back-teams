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
        return response()->json([
            'status' => 'success',
            'data' => $this->formatResponse($setting),
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
            'popup_start_delay_seconds' => 'popup_start_delay_seconds',
            'popupInicioDelay' => 'popup_start_delay_seconds',
            'popup_start_delay_minutes' => 'popup_start_delay_seconds',
            'product_popup_delay_seconds' => 'product_popup_delay_seconds',
            'popupProductosDelay' => 'product_popup_delay_seconds',
            'product_popup_delay_minutes' => 'product_popup_delay_seconds',
            'whatsapp_message' => 'whatsapp_message',
            'whatsappMessage' => 'whatsapp_message',
            'email_subject' => 'email_subject',
            'emailTitle' => 'email_subject',
            'email_message' => 'email_message',
            'emailBody' => 'email_message',
            'email_btn_text' => 'email_btn_text',
            'emailBtnText' => 'email_btn_text',
            'email_btn_link' => 'email_btn_link',
            'emailBtnLink' => 'email_btn_link',
            'email_btn_bg_color' => 'email_btn_bg_color',
            'emailBtnBgColor' => 'email_btn_bg_color',
            'email_btn_text_color' => 'email_btn_text_color',
            'emailBtnTextColor' => 'email_btn_text_color',
        ];

        foreach ($textMapping as $frontKey => $dbColumn) {
            if ($request->has($frontKey)) {
                $data[$dbColumn] = $request->input($frontKey);
            }
        }

        // Mapeo de imágenes mejorado para soportar múltiples variantes de nombres de columnas
        $imageFields = [
            'image1'              => ['popup_image_url'],
            'popup_image'         => ['popup_image_url'],
            'image2'              => ['popup_image_2_url', 'popup_image2_url'],
            'popup_image_2'       => ['popup_image_2_url', 'popup_image2_url'],
            'popup_image2'        => ['popup_image_2_url', 'popup_image2_url'],
            'imageMobile'         => ['popup_mobile_image_url', 'popup_mobile_image_1_url'],
            'popup_mobile_image'  => ['popup_mobile_image_url', 'popup_mobile_image_1_url'],
            'imageMobile2'        => ['popup_mobile_image2_url', 'popup_mobile_image_2_url'],
            'popup_mobile_image2' => ['popup_mobile_image2_url', 'popup_mobile_image_2_url'],
            'whatsappImage'       => ['whatsapp_image_url'],
            'whatsapp_image'      => ['whatsapp_image_url'],
            'emailImage'          => ['email_image_url'],
            'email_image'         => ['email_image_url'],
        ];

        foreach ($imageFields as $fileInput => $dbColumns) {
            $mainColumn = $dbColumns[0];
            
            if ($request->hasFile($fileInput)) {
                $path = $this->replaceImage(
                    $request->file($fileInput),
                    $setting->$mainColumn
                );
                
                foreach ($dbColumns as $col) {
                    $data[$col] = $path;
                }
            } elseif ($request->boolean('delete_' . $fileInput)) {
                if (!empty($setting->$mainColumn)) {
                    $this->deleteImage($setting->$mainColumn);
                }
                foreach ($dbColumns as $col) {
                    $data[$col] = null;
                }
            }
        }

        $data['enabled'] = true;
        $data['updated_by'] = Auth::id();

        \Log::info('--- FINAL SAVE DATA ---', $data);

        $setting->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Configuración de popup actualizada correctamente.',
            'data' => $this->formatResponse($setting->fresh()),
        ]);
    }

    public function showPublic(): JsonResponse
    {
        $setting = $this->getOrCreateSettings();

        return response()->json([
            'status' => 'success',
            'data' => $this->formatResponse($setting),
        ]);
    }

    private function getOrCreateSettings(): HomePopupSetting
    {
        return HomePopupSetting::firstOrCreate([], [
            'enabled' => true,
            'popup_start_delay_seconds' => 60,
            'product_popup_delay_seconds' => 60,
            'button_text' => '!REGISTRARME!',
            'button_bg_color' => '#00AFA0',
            'button_text_color' => '#FFFFFF',
            'whatsapp_enabled' => false,
            'email_enabled' => false,
            'email_btn_text' => '¡REGISTRARME!',
            'email_btn_bg_color' => '#00AFA0',
            'email_btn_text_color' => '#FFFFFF',
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

    private function formatResponse(HomePopupSetting $setting): array
    {
        // Obtener lista exacta de columnas de la tabla para convertirlas a absolutas
        $schemaColumns = [
            'popup_image_url', 'popup_image_2_url', 'popup_image2_url',
            'popup_mobile_image_url', 'popup_mobile_image2_url', 'popup_mobile_image_1_url', 'popup_mobile_image_2_url',
            'whatsapp_image_url', 'email_image_url'
        ];
        
        $data = $setting->toArray();

        foreach ($schemaColumns as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = url($data[$field]);
            }
        }

        // Unificar Aliases para el Frontend (Priorizando versiones con datos)
        $data['image1'] = $data['popup_image_url'] ?? null;
        $data['image2'] = $data['popup_image_2_url'] ?? $data['popup_image2_url'] ?? null;
        $data['imageMobile'] = $data['popup_mobile_image_url'] ?? $data['popup_mobile_image_1_url'] ?? null;
        $data['imageMobile2'] = $data['popup_mobile_image2_url'] ?? $data['popup_mobile_image_2_url'] ?? null;
        
        $data['whatsappImage'] = $data['whatsapp_image_url'] ?? null;
        $data['emailImage'] = $data['email_image_url'] ?? null;

        // Variables de diseño y texto
        $data['btnTextColor'] = $setting->button_text_color;
        $data['btnBgColor'] = $setting->button_bg_color;
        $data['buttonText'] = $setting->button_text;
        
        $data['popupInicioDelay'] = $setting->popup_start_delay_seconds;
        $data['popupProductosDelay'] = $setting->product_popup_delay_seconds;
        
        // El frontend busca popup_start_delay_minutes en usePopupLogic.ts
        $data['popup_start_delay_minutes'] = $setting->popup_start_delay_seconds;
        
        $data['whatsappMessage'] = $setting->whatsapp_message;
        $data['emailTitle'] = $setting->email_subject;
        $data['emailBody'] = $setting->email_message;
        
        $data['emailBtnText'] = $setting->email_btn_text;
        $data['emailBtnLink'] = $setting->email_btn_link;
        $data['emailBtnBgColor'] = $setting->email_btn_bg_color;
        $data['emailBtnTextColor'] = $setting->email_btn_text_color;

        return $data;
    }
}
