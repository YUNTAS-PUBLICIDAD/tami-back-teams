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
            'data' => $setting,
        ]);
    }

    public function update(UpdateHomePopupSettingRequest $request): JsonResponse
    {
        $setting = $this->getOrCreateSettings();
        $data = $request->validated();

        if ($request->hasFile('popup_image')) {
            $data['popup_image_url'] = $this->replaceImage(
                $request->file('popup_image'),
                $setting->popup_image_url
            );
        }

        unset($data['popup_image']);
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

    private function replaceImage(UploadedFile $file, ?string $oldPublicUrl): string
    {
        if (!empty($oldPublicUrl)) {
            $oldPath = str_replace('/storage/', '', $oldPublicUrl);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $storedPath = $file->store('home-popup', 'public');

        return '/storage/' . $storedPath;
    }
}
