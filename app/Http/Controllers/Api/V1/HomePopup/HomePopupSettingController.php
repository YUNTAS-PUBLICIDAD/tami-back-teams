<?php

namespace App\Http\Controllers\Api\V1\HomePopup;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomePopup\UpdateHomePopupSettingRequest;
use App\Models\HomePopupSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class HomePopupSettingController extends Controller
{
    public function showAdmin(): JsonResponse
    {
        $setting = $this->getOrCreateSettings();

        return response()->json([
            'status' => 'success',
            'data' => $setting,
        ]);
    }

    public function update(UpdateHomePopupSettingRequest $request): JsonResponse
    {
        $setting = $this->getOrCreateSettings();
        $data = $request->validated();

        $data['updated_by'] = Auth::id();

        $setting->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Configuracion de popup de inicio actualizada correctamente.',
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
                'title' => $setting->title,
                'subtitle' => $setting->subtitle,
                'popup_image_url' => $setting->popup_image_url,
                'button_text' => $setting->button_text,
                'button_bg_color' => $setting->button_bg_color,
                'button_text_color' => $setting->button_text_color,
                'whatsapp_enabled' => $setting->whatsapp_enabled,
                'whatsapp_message' => $setting->whatsapp_message,
                'whatsapp_image_url' => $setting->whatsapp_image_url,
                'email_enabled' => $setting->email_enabled,
                'email_subject' => $setting->email_subject,
                'email_message' => $setting->email_message,
                'email_image_url' => $setting->email_image_url,
            ],
        ]);
    }

    private function getOrCreateSettings(): HomePopupSetting
    {
        return HomePopupSetting::firstOrCreate([], [
            'enabled' => false,
            'button_text' => '!REGISTRARME!',
            'button_bg_color' => '#00AFA0',
            'button_text_color' => '#FFFFFF',
            'whatsapp_enabled' => false,
            'email_enabled' => false,
        ]);
    }
}
