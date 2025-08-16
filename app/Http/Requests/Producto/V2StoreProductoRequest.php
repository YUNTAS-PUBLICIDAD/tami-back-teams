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
            'precio' => "nullable|string|max:100000|min:0",
            'seccion' => "nullable|string|max:255",
            'descripcion' => "nullable|string|max:65535",

            // Etiquetas SEO
            'etiquetas' => "nullable|array",
            'etiquetas.meta_titulo' => "nullable|string|max:60",
            'etiquetas.meta_descripcion' => "nullable|string|max:160",

            // Especificaciones
            'especificaciones' => "string|max:65535",

            // Imágenes y textos_alt
            'imagenes' => "array|min:1|max:10",
            'imagenes.*' => "file|image|max:2048",
            'textos_alt' => "array|min:1|max:10",
            'textos_alt.*' => "string|max:255",

            // Productos relacionados
            'relacionados' => "sometimes|array",
            'relacionados.*' => "integer|exists:productos,id",
        ];
    }
}
