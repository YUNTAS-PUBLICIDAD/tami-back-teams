<?php

namespace App\Http\Requests\HomePopup;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomePopupSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'button_bg_color' => ['sometimes', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'button_text_color' => ['sometimes', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'popup_image' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
        ];
    }
}
