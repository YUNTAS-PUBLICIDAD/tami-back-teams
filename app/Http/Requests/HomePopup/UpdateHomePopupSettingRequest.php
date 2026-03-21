<?php

namespace App\Http\Requests\HomePopup;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomePopupSettingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Si el frontend envía un string con el nombre de la imagen vieja (ej: "silla.webp") 
        // en lugar de un archivo real (File), fallará la regla de validación 'file|image'.
        // Para evitarlo, eliminamos la variable del request si no es un archivo válido.
        $imageKeys = ['image1', 'image2', 'imageMobile', 'whatsappImage', 'emailImage'];
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
            // Standard original fields
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'button_text' => ['sometimes', 'string', 'max:255'],
            'enabled' => ['sometimes', 'boolean'],
            'whatsapp_enabled' => ['sometimes', 'boolean'],
            'email_enabled' => ['sometimes', 'boolean'],

            // Textos, Tiempos y Colores (Frontend Payload)
            'btnTextColor' => ['sometimes', 'string', 'regex:/^#([A-Fa-f0-9]{3,6})$/'],
            'btnBgColor' => ['sometimes', 'string', 'regex:/^#([A-Fa-f0-9]{3,6})$/'],
            'popupInicioDelay' => ['sometimes', 'numeric', 'min:1'],
            'popupProductosDelay' => ['sometimes', 'numeric', 'min:1'],
            'whatsappMessage' => ['sometimes', 'nullable', 'string'],
            'emailTitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'emailBody' => ['sometimes', 'nullable', 'string'],

            // Archivos de Imagen
            'image1' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'image2' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'imageMobile' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'whatsappImage' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'emailImage' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            
            // Deletion flags
            'delete_image1' => 'sometimes|boolean',
            'delete_image2' => 'sometimes|boolean',
            'delete_imageMobile' => 'sometimes|boolean',
            'delete_whatsappImage' => 'sometimes|boolean',
            'delete_emailImage' => 'sometimes|boolean',
        ];
    }
}
