<?php

namespace App\Http\Requests\HomePopup;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomePopupSettingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $aliases = [
            'popup_delay_minutes',
            'home_popup_time',
            'home_popup_delay_minutes',
            'popup_time_minutes',
            'start_popup_delay_minutes',
        ];

        foreach ($aliases as $alias) {
            if ($this->has($alias) && !$this->has('popup_start_delay_minutes')) {
                $this->merge([
                    'popup_start_delay_minutes' => $this->input($alias),
                ]);
                break;
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'popup_start_delay_minutes' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'button_bg_color' => ['sometimes', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'button_text_color' => ['sometimes', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'popup_image' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
        ];
    }
}
