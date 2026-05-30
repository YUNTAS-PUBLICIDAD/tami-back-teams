<?php

namespace App\Http\Controllers\Api\V1\HomePopup;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomePopup\UpdateHomePopupSettingRequest;
use App\Models\HomePopupSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class HomePopupSettingController extends Controller
{
    public function showAdmin(): JsonResponse
    {
        $setting = $this->getOrCreateSettings();
        $popupType = $this->resolvePopupType(request());

        return response()->json([
            'status' => 'success',
            'data' => $this->formatResponse($setting, $popupType),
        ]);
    }

    public function update(UpdateHomePopupSettingRequest $request): JsonResponse
    {
        $setting = $this->getOrCreateSettings();

        // Log para depuración
        Log::info('Petición de actualización de popup recibida', $request->all());

        // Detectar tipo de popup: 'inicio' o 'producto' (por defecto 'inicio')
        $popupType = $this->resolvePopupType($request);

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

        // Mapeo de campos de texto con soporte para tipo de popup
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
            'email_send_delay_minutes' => 'email_send_delay_minutes',
            'emailSendDelay' => 'email_send_delay_minutes',

            // Correo 2
            'emailTitle_2' => 'email_subject_2',
            'emailBody_2' => 'email_message_2',
            'email_btn_text_2' => 'email_btn_text_2',
            'email_btn_link_2' => 'email_btn_link_2',
            'email_btn_bg_color_2' => 'email_btn_bg_color_2',
            'email_btn_text_color_2' => 'email_btn_text_color_2',
            'email_send_delay_minutes_2' => 'email_send_delay_minutes_2',

            // Correo 3
            'emailTitle_3' => 'email_subject_3',
            'emailBody_3' => 'email_message_3',
            'email_btn_text_3' => 'email_btn_text_3',
            'email_btn_link_3' => 'email_btn_link_3',
            'email_btn_bg_color_3' => 'email_btn_bg_color_3',
            'email_btn_text_color_3' => 'email_btn_text_color_3',
            'email_send_delay_minutes_3' => 'email_send_delay_minutes_3',
            'popup_mobile_image_count' => 'popup_mobile_image_count',
            'popupMobileImageCount' => 'popup_mobile_image_count',
        ];

        // Agregar campos de WhatsApp según el tipo de popup
        if ($popupType === 'inicio') {
            $textMapping['whatsapp_message'] = 'whatsapp_message_inicio';
            $textMapping['whatsappMessage'] = 'whatsapp_message_inicio';
            $textMapping['whatsapp_message_2'] = 'whatsapp_message_2_inicio';
            $textMapping['whatsappMessage2'] = 'whatsapp_message_2_inicio';
            $textMapping['whatsapp_message_3'] = 'whatsapp_message_3_inicio';
            $textMapping['whatsappMessage3'] = 'whatsapp_message_3_inicio';
            $textMapping['whatsapp_time_1'] = 'whatsapp_time_1_inicio';
            $textMapping['whatsappTime1'] = 'whatsapp_time_1_inicio';
            $textMapping['whatsapp_time_2'] = 'whatsapp_time_2_inicio';
            $textMapping['whatsappTime2'] = 'whatsapp_time_2_inicio';
            $textMapping['whatsapp_time_3'] = 'whatsapp_time_3_inicio';
            $textMapping['whatsappTime3'] = 'whatsapp_time_3_inicio';
        } elseif ($popupType === 'producto') {
            $textMapping['whatsapp_message'] = 'whatsapp_message_producto';
            $textMapping['whatsappMessage'] = 'whatsapp_message_producto';
            $textMapping['whatsapp_message_2'] = 'whatsapp_message_2_producto';
            $textMapping['whatsappMessage2'] = 'whatsapp_message_2_producto';
            $textMapping['whatsapp_message_3'] = 'whatsapp_message_3_producto';
            $textMapping['whatsappMessage3'] = 'whatsapp_message_3_producto';
            $textMapping['whatsapp_time_1'] = 'whatsapp_time_1_producto';
            $textMapping['whatsappTime1'] = 'whatsapp_time_1_producto';
            $textMapping['whatsapp_time_2'] = 'whatsapp_time_2_producto';
            $textMapping['whatsappTime2'] = 'whatsapp_time_2_producto';
            $textMapping['whatsapp_time_3'] = 'whatsapp_time_3_producto';
            $textMapping['whatsappTime3'] = 'whatsapp_time_3_producto';
        }

        foreach ($textMapping as $frontKey => $dbColumn) {
            if ($request->has($frontKey)) {
                $data[$dbColumn] = $request->input($frontKey);
            }
        }

        // Mapeo de imágenes mejorado con soporte para tipo de popup
        $imageFields = [
            'image1'              => ['popup_image_url'],
            'popup_image'         => ['popup_image_url'],
            'image2'              => ['popup_image_2_url', 'popup_image2_url'],
            'popup_image_2'       => ['popup_image_2_url', 'popup_image2_url'],
            'popup_image2'        => ['popup_image_2_url', 'popup_image2_url'],
            'imageMobile'         => ['popup_mobile_image_url', 'popup_mobile_image_1_url'],
            'popup_mobile_image'  => ['popup_mobile_image_url', 'popup_mobile_image_1_url'],
            'imagen_popup_mobile' => ['popup_mobile_image_url', 'popup_mobile_image_1_url'],
            'imageMobile2'        => ['popup_mobile_image2_url', 'popup_mobile_image_2_url'],
            'popup_mobile_image2' => ['popup_mobile_image2_url', 'popup_mobile_image_2_url'],
            'imagen_popup_mobile2'=> ['popup_mobile_image2_url', 'popup_mobile_image_2_url'],
            'emailImage'          => ['email_image_url'],
            'email_image'         => ['email_image_url'],
            'emailImage_2'        => ['email_image_url_2'],
            'emailImage_3'        => ['email_image_url_3'],
        ];

        // Agregar imágenes de WhatsApp según el tipo de popup
        if ($popupType === 'inicio') {
            $imageFields['whatsappImage']  = ['whatsapp_image_url_inicio'];
            $imageFields['whatsapp_image'] = ['whatsapp_image_url_inicio'];
            $imageFields['whatsappImage2'] = ['whatsapp_image_url_2_inicio'];
            $imageFields['whatsapp_image_2'] = ['whatsapp_image_url_2_inicio'];
            $imageFields['whatsappImage3'] = ['whatsapp_image_url_3_inicio'];
            $imageFields['whatsapp_image_3'] = ['whatsapp_image_url_3_inicio'];
        } elseif ($popupType === 'producto') {
            $imageFields['whatsappImage']  = ['whatsapp_image_url_producto'];
            $imageFields['whatsapp_image'] = ['whatsapp_image_url_producto'];
            $imageFields['whatsappImage2'] = ['whatsapp_image_url_2_producto'];
            $imageFields['whatsapp_image_2'] = ['whatsapp_image_url_2_producto'];
            $imageFields['whatsappImage3'] = ['whatsapp_image_url_3_producto'];
            $imageFields['whatsapp_image_3'] = ['whatsapp_image_url_3_producto'];
        }

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
            } elseif (in_array($fileInput, ['imageMobile', 'popup_mobile_image', 'imagen_popup_mobile'], true) && $request->boolean('delete_popup_mobile')) {
                if (!empty($setting->$mainColumn)) {
                    $this->deleteImage($setting->$mainColumn);
                }
                foreach ($dbColumns as $col) {
                    $data[$col] = null;
                }
            } elseif (in_array($fileInput, ['imageMobile2', 'popup_mobile_image2', 'imagen_popup_mobile2'], true) && $request->boolean('delete_popup_mobile2')) {
                if (!empty($setting->$mainColumn)) {
                    $this->deleteImage($setting->$mainColumn);
                }
                foreach ($dbColumns as $col) {
                    $data[$col] = null;
                }
            }
        }

        // Mantener los campos genéricos sincronizados con el popup activo
        if ($popupType === 'producto') {
            $data['whatsapp_message'] = $data['whatsapp_message_producto'] ?? $setting->whatsapp_message_producto;
            $data['whatsapp_message_2'] = $data['whatsapp_message_2_producto'] ?? $setting->whatsapp_message_2_producto;
            $data['whatsapp_message_3'] = $data['whatsapp_message_3_producto'] ?? $setting->whatsapp_message_3_producto;
            $data['whatsapp_time_1'] = $data['whatsapp_time_1_producto'] ?? $setting->whatsapp_time_1_producto;
            $data['whatsapp_time_2'] = $data['whatsapp_time_2_producto'] ?? $setting->whatsapp_time_2_producto;
            $data['whatsapp_time_3'] = $data['whatsapp_time_3_producto'] ?? $setting->whatsapp_time_3_producto;
            $data['whatsapp_image_url'] = $data['whatsapp_image_url_producto'] ?? $setting->whatsapp_image_url_producto;
            $data['whatsapp_image_url_2'] = $data['whatsapp_image_url_2_producto'] ?? $setting->whatsapp_image_url_2_producto;
            $data['whatsapp_image_url_3'] = $data['whatsapp_image_url_3_producto'] ?? $setting->whatsapp_image_url_3_producto;
        } else {
            $data['whatsapp_message'] = $data['whatsapp_message_inicio'] ?? $setting->whatsapp_message_inicio;
            $data['whatsapp_message_2'] = $data['whatsapp_message_2_inicio'] ?? $setting->whatsapp_message_2_inicio;
            $data['whatsapp_message_3'] = $data['whatsapp_message_3_inicio'] ?? $setting->whatsapp_message_3_inicio;
            $data['whatsapp_time_1'] = $data['whatsapp_time_1_inicio'] ?? $setting->whatsapp_time_1_inicio;
            $data['whatsapp_time_2'] = $data['whatsapp_time_2_inicio'] ?? $setting->whatsapp_time_2_inicio;
            $data['whatsapp_time_3'] = $data['whatsapp_time_3_inicio'] ?? $setting->whatsapp_time_3_inicio;
            $data['whatsapp_image_url'] = $data['whatsapp_image_url_inicio'] ?? $setting->whatsapp_image_url_inicio;
            $data['whatsapp_image_url_2'] = $data['whatsapp_image_url_2_inicio'] ?? $setting->whatsapp_image_url_2_inicio;
            $data['whatsapp_image_url_3'] = $data['whatsapp_image_url_3_inicio'] ?? $setting->whatsapp_image_url_3_inicio;
        }

        $data['enabled'] = true;
        $data['updated_by'] = Auth::id();

        Log::info('--- FINAL SAVE DATA ---', $data);

        $setting->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Configuración de popup actualizada correctamente.',
            'data' => $this->formatResponse($setting->fresh(), $popupType),
        ]);
    }

    public function showPublic(): JsonResponse
    {
        $setting = $this->getOrCreateSettings();
        $popupType = $this->resolvePopupType(request());

        return response()->json([
            'status' => 'success',
            'data' => $this->formatResponse($setting, $popupType),
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
            'whatsapp_time_1' => 0,
            'whatsapp_time_2' => 0,
            'whatsapp_time_3' => 0,
            'email_enabled' => false,
            'email_btn_text' => '¡REGISTRARME!',
            'email_btn_bg_color' => '#00AFA0',
            'email_btn_text_color' => '#FFFFFF',
            'email_send_delay_minutes' => 0,
            'email_send_delay_minutes_2' => 30,
            'email_send_delay_minutes_3' => 1440,
            'popup_mobile_image_count' => 2,
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

    private function resolvePopupType($request): string
    {
        if ($request->has('popup_type')) {
            return $request->input('popup_type');
        }

        if (
            $request->has('popup_mobile_image_count')
            || $request->has('popupMobileImageCount')
            || $request->hasFile('popup_mobile_image')
            || $request->hasFile('popup_mobile_image2')
            || $request->hasFile('imagen_popup_mobile')
            || $request->hasFile('imagen_popup_mobile2')
            || $request->has('popup_mobile_image')
            || $request->has('popup_mobile_image2')
            || $request->has('imagen_popup_mobile')
            || $request->has('imagen_popup_mobile2')
        ) {
            return 'producto';
        }

        if ($request->has('producto_id') || $request->has('product_id') || $request->has('selected_product_id')) {
            return 'producto';
        }

        $referer = strtolower((string) $request->headers->get('referer', ''));
        if (!empty($referer)) {
            if (str_contains($referer, 'producto')) {
                return 'producto';
            }

            if (str_contains($referer, 'inicio')) {
                return 'inicio';
            }
        }

        // Heurística compatible con el frontend actual:
        // si el request trae los campos de Producto, tratamos el guardado como Producto.
        if (
            $request->has('product_popup_delay_seconds')
            || $request->has('popupProductosDelay')
            || $request->has('product_popup_delay_minutes')
        ) {
            return 'producto';
        }

        return 'inicio';
    }

    private function formatResponse(HomePopupSetting $setting, $popupType = 'inicio'): array
    {
        // Obtener lista exacta de columnas de la tabla para convertirlas a absolutas
        $schemaColumns = [
            'popup_image_url', 'popup_image_2_url', 'popup_image2_url',
            'popup_mobile_image_url', 'popup_mobile_image2_url', 'popup_mobile_image_1_url', 'popup_mobile_image_2_url',
            'whatsapp_image_url', 'whatsapp_image_url_2', 'whatsapp_image_url_3',
            'whatsapp_image_url_inicio', 'whatsapp_image_url_2_inicio', 'whatsapp_image_url_3_inicio',
            'whatsapp_image_url_producto', 'whatsapp_image_url_2_producto', 'whatsapp_image_url_3_producto',
            'email_image_url', 'email_image_url_2', 'email_image_url_3'
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

        // Seleccionar imágenes según el tipo de popup
        if ($popupType === 'producto') {
            $data['whatsappImage'] = $data['whatsapp_image_url_producto'] ?? null;
            $data['whatsappImage2'] = $data['whatsapp_image_url_2_producto'] ?? null;
            $data['whatsappImage3'] = $data['whatsapp_image_url_3_producto'] ?? null;

            $data['whatsapp_image_url'] = $data['whatsapp_image_url_producto'] ?? null;
            $data['whatsapp_image_url_2'] = $data['whatsapp_image_url_2_producto'] ?? null;
            $data['whatsapp_image_url_3'] = $data['whatsapp_image_url_3_producto'] ?? null;

            $data['whatsappMessage'] = $setting->whatsapp_message_producto;
            $data['whatsappMessage2'] = $setting->whatsapp_message_2_producto;
            $data['whatsappMessage3'] = $setting->whatsapp_message_3_producto;
            $data['whatsapp_message'] = $setting->whatsapp_message_producto;
            $data['whatsapp_message_2'] = $setting->whatsapp_message_2_producto;
            $data['whatsapp_message_3'] = $setting->whatsapp_message_3_producto;
            $data['whatsappTime1'] = $setting->whatsapp_time_1_producto;
            $data['whatsappTime2'] = $setting->whatsapp_time_2_producto;
            $data['whatsappTime3'] = $setting->whatsapp_time_3_producto;
            $data['whatsapp_time_1'] = $setting->whatsapp_time_1_producto;
            $data['whatsapp_time_2'] = $setting->whatsapp_time_2_producto;
            $data['whatsapp_time_3'] = $setting->whatsapp_time_3_producto;
        } else {
            // Por defecto 'inicio'
            $data['whatsappImage'] = $data['whatsapp_image_url_inicio'] ?? null;
            $data['whatsappImage2'] = $data['whatsapp_image_url_2_inicio'] ?? null;
            $data['whatsappImage3'] = $data['whatsapp_image_url_3_inicio'] ?? null;

            $data['whatsapp_image_url'] = $data['whatsapp_image_url_inicio'] ?? null;
            $data['whatsapp_image_url_2'] = $data['whatsapp_image_url_2_inicio'] ?? null;
            $data['whatsapp_image_url_3'] = $data['whatsapp_image_url_3_inicio'] ?? null;

            $data['whatsappMessage'] = $setting->whatsapp_message_inicio;
            $data['whatsappMessage2'] = $setting->whatsapp_message_2_inicio;
            $data['whatsappMessage3'] = $setting->whatsapp_message_3_inicio;
            $data['whatsapp_message'] = $setting->whatsapp_message_inicio;
            $data['whatsapp_message_2'] = $setting->whatsapp_message_2_inicio;
            $data['whatsapp_message_3'] = $setting->whatsapp_message_3_inicio;
            $data['whatsappTime1'] = $setting->whatsapp_time_1_inicio;
            $data['whatsappTime2'] = $setting->whatsapp_time_2_inicio;
            $data['whatsappTime3'] = $setting->whatsapp_time_3_inicio;
            $data['whatsapp_time_1'] = $setting->whatsapp_time_1_inicio;
            $data['whatsapp_time_2'] = $setting->whatsapp_time_2_inicio;
            $data['whatsapp_time_3'] = $setting->whatsapp_time_3_inicio;
        }

        $data['emailImage'] = $data['email_image_url'] ?? null;
        $data['emailImage_2'] = $data['email_image_url_2'] ?? null;
        $data['emailImage_3'] = $data['email_image_url_3'] ?? null;

        // Variables de diseño y texto
        $data['btnTextColor'] = $setting->button_text_color;
        $data['btnBgColor'] = $setting->button_bg_color;
        $data['buttonText'] = $setting->button_text;

        $data['popupInicioDelay'] = $setting->popup_start_delay_seconds;
        $data['popupProductosDelay'] = $setting->product_popup_delay_seconds;

        // El frontend busca popup_start_delay_minutes en usePopupLogic.ts
        $data['popup_start_delay_minutes'] = $setting->popup_start_delay_seconds;

        $data['emailTitle'] = $setting->email_subject;
        $data['emailBody'] = $setting->email_message;

        $data['emailBtnText'] = $setting->email_btn_text;
        $data['emailBtnLink'] = $setting->email_btn_link;
        $data['emailBtnBgColor'] = $setting->email_btn_bg_color;
        $data['emailBtnTextColor'] = $setting->email_btn_text_color;
        $data['emailSendDelay'] = $setting->email_send_delay_minutes;

        // Correo 2
        $data['emailTitle_2'] = $setting->email_subject_2;
        $data['emailBody_2'] = $setting->email_message_2;
        $data['emailBtnText_2'] = $setting->email_btn_text_2;
        $data['emailBtnLink_2'] = $setting->email_btn_link_2;
        $data['emailBtnBgColor_2'] = $setting->email_btn_bg_color_2;
        $data['emailBtnTextColor_2'] = $setting->email_btn_text_color_2;
        $data['emailSendDelay_2'] = $setting->email_send_delay_minutes_2;

        // Correo 3
        $data['emailTitle_3'] = $setting->email_subject_3;
        $data['emailBody_3'] = $setting->email_message_3;
        $data['emailBtnText_3'] = $setting->email_btn_text_3;
        $data['emailBtnLink_3'] = $setting->email_btn_link_3;
        $data['emailBtnBgColor_3'] = $setting->email_btn_bg_color_3;
        $data['emailBtnTextColor_3'] = $setting->email_btn_text_color_3;
        $data['emailSendDelay_3'] = $setting->email_send_delay_minutes_3;
        $data['popup_type'] = $popupType;

        return $data;
    }
}
