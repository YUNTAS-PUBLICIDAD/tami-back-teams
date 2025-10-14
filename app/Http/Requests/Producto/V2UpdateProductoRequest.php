<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class V2UpdateProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isPut = $this->isMethod('put');
        $required = $isPut ? 'required' : 'sometimes';
        $productoId = $this->route('id');

        return [
            'nombre' => [$required, 'string', 'max:255', Rule::unique('productos', 'nombre')->ignore($productoId)],
            'link' => [$required, 'string', 'max:255', Rule::unique('productos', 'link')->ignore($productoId)],
            'titulo' => [$required, 'string', 'max:255'],
            'subtitulo' => [$required, 'string', 'max:255'],
            'stock' => [$required, 'integer', 'max:1000', 'min:0'],
            'precio' => [$required, 'numeric', 'min:0'],
            'seccion' => [$required, 'string', 'max:255'],
            'descripcion' => [$required, 'string', 'max:65535'],
            'meta_titulo' => 'nullable|string|min:10|max:60',
            'meta_descripcion' => 'nullable|string|min:40|max:160',
            'especificaciones' => [$required, 'string', 'max:65535'],
            'keywords' => [$required, 'string', 'max:65535'],
            'dimensiones' => 'array',
            'dimensiones.alto' => "nullable|numeric|min:0",
            'dimensiones.largo' => "nullable|numeric|min:0",
            'dimensiones.ancho' => "nullable|numeric|min:0",
            'imagenes' => ['sometimes', 'array'],
            'imagenes.*' => ['file', 'image', 'max:2048'],
            'textos_alt' => ['sometimes', 'array'],
            'textos_alt.*' => ['string', 'max:255'],
            
            // Imagen popup
            'imagen_popup' => ['nullable', 'file', 'image', 'max:2048'],
            'texto_alt_popup' => ['nullable', 'string', 'max:255'],
            
            // Imagen email
            'imagen_email' => ['nullable', 'file', 'image', 'max:2048'],
            'texto_alt_email' => ['nullable', 'string', 'max:255'],
            
            // Imagen Whatsapp
            'imagen_Whatsapp' => ['nullable', 'file', 'image', 'max:2048'],
            'texto_alt_Whatsapp' => ['nullable', 'string', 'max:255'],
            
            
            // URL del video
            'video_url' => ['nullable', 'url', 'max:500'],
            
            'relacionados' => ['sometimes', 'array'],
            'relacionados.*' => ['integer', 'exists:productos,id'],
        ];
    }
}
