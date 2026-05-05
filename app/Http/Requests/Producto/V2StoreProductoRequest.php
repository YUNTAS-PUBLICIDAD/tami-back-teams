<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;

class V2StoreProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Convert values before validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('relacionados') && is_array($this->relacionados)) {
            $this->merge([
                'relacionados' => array_map('intval', $this->relacionados)
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => "required|string|max:255",
            'nombre' => "required|string|max:255|unique:productos,nombre",
            'link' => 'required|string|unique:productos,link|max:255',
            'subtitulo' => "nullable|string|max:255",
            'stock' => "nullable|integer|max:1000|min:0",
            'precio' => "nullable|numeric|max:100000|min:0",
            'seccion' => "nullable|string|max:255",
            'descripcion' => "nullable|string|max:65535",

            // Etiquetas SEO
            'meta_titulo' => 'nullable|string|min:10|max:70',
            'meta_descripcion' => 'nullable|string|min:40|max:200',
            'keywords' => "string|max:65535",

            // Especificaciones
            'especificaciones' => "string|max:65535",

            // Dimensiones
            'dimensiones' => 'array',
            'dimensiones.alto' => "nullable|numeric|min:0",
            'dimensiones.largo' => "nullable|numeric|min:0",
            'dimensiones.ancho' => "nullable|numeric|min:0",

            // Imágenes y textos_alt
            'imagenes' => "array|min:1|max:10",
            'imagenes.*' => "file|image|max:3048",
            'textos_alt' => "array|min:1|max:10",
            'textos_alt.*' => "string|max:255",

            // Imagen popup
            'imagen_popup' => "nullable|file|image|max:3048",
            'texto_alt_popup' => "nullable|string|max:255",
            'imagen_popup2' => "nullable|file|image|max:3048",
            'texto_alt_popup2' => "nullable|string|max:255",

            'imagen_email' => "nullable|file|image|max:3048",
            'texto_alt_email' => "nullable|string|max:255",
            'asunto' => ['nullable', 'string', 'max:255'],
            'mensaje_email' => 'nullable|string',
            'email_btn_text' => 'nullable|string|max:100',
            'email_btn_link' => 'nullable|url|max:255',
            'email_btn_bg_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6})$/',
            'email_btn_text_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6})$/',
            
            // Imagen Whatsapp
            'imagen_whatsapp' => "nullable|file|image|max:3048",
            'texto_alt_whatsapp' => "nullable|string|max:2000",
            'mensaje_whatsapp' => 'nullable|string',

            // URL del video
            'video_url' => "nullable|url|max:500",

            // Productos relacionados
            'relacionados' => "sometimes|array",
            'relacionados.*' => "integer|exists:productos,id",
        ];
    }
}
