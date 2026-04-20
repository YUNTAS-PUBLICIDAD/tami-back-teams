<?php

namespace App\Http\Requests\HomePopup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateHomePopupSettingRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        \Log::error('Validation Failed in Popup Settings:', $validator->errors()->toArray());
        parent::failedValidation($validator);
    }

    protected function prepareForValidation(): void
    {
        // Si el frontend envía un string con el nombre de la imagen vieja (ej: "silla.webp") 
        // en lugar de un archivo real (File), fallará la regla de validación 'file|image'.
        // Para evitarlo, eliminamos la variable del request si no es un archivo válido.
        $imageKeys = ['image1', 'image2', 'imageMobile', 'imageMobile2', 'whatsappImage', 'emailImage'];
        foreach ($imageKeys as $key) {
            if ($this->has($key) && !$this->hasFile($key)) {
                $this->request->remove($key);
            }
        }

        $aliases = [
            'popup_delay_minutes',
            'home_popup_time',
            'home_popup_delay_minutes',
            'popup_time_minutes',
            'start_popup_delay_minutes',
            'popup_start_delay_minutes',
            'popupInicioDelay',
            'popup_delay_seconds',
        ];

        foreach ($aliases as $alias) {
            if ($this->has($alias) && !$this->has('popup_start_delay_seconds')) {
                $this->merge([
                    'popup_start_delay_seconds' => $this->input($alias),
                ]);
                break;
            }
        }

        if ($this->has('popupProductosDelay') && !$this->has('product_popup_delay_seconds')) {
            $this->merge([
                'product_popup_delay_seconds' => $this->input('popupProductosDelay'),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],
            'title' => ['sometimes', 'nullable', 'string', 'max:150'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'button_text' => ['sometimes', 'nullable', 'string', 'max:50'],
            'buttonText' => ['sometimes', 'nullable', 'string', 'max:50'],
            'button_bg_color' => ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'btnBgColor' => ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'button_text_color' => ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'btnTextColor' => ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'whatsapp_enabled' => ['sometimes', 'boolean'],
            'whatsappMessage' => ['sometimes', 'nullable', 'string'],
            'email_enabled' => ['sometimes', 'boolean'],
            'emailTitle' => ['sometimes', 'nullable', 'string', 'max:200'],
            'emailBody' => ['sometimes', 'nullable', 'string'],
            'email_btn_text' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email_btn_link' => ['sometimes', 'nullable', 'url', 'max:255'],
            'email_btn_bg_color' => ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'email_btn_text_color' => ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'popup_start_delay_seconds' => ['sometimes', 'integer', 'min:1', 'max:3600'],
            'product_popup_delay_seconds' => ['sometimes', 'integer', 'min:1', 'max:3600'],
            'popup_start_delay_minutes' => ['sometimes', 'integer'],
            'product_popup_delay_minutes' => ['sometimes', 'integer'],
            'image1' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'image2' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'imageMobile' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'imageMobile2' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'whatsappImage' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'emailImage' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'popup_image' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'popup_image_2' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
        ];
    }
}
