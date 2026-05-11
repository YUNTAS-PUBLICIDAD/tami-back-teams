<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class V2UpdateProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::error('V2UpdateProductoRequest validation failed', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->all()
        ]);

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422)
        );
    }

    protected function prepareForValidation(): void
    {
        $style = $this->input('detalle_titulo_estilo')
            ?? $this->input('titulo_detalle_estilo')
            ?? $this->input('title_style');

        if (is_string($style)) {
            $style = str_replace(['+', ' '], '_', mb_strtolower(trim($style)));
        }

        $tamano = $this->input('detalle_titulo_tamano')
            ?? $this->input('titulo_detalle_tamano')
            ?? $this->input('title_size');

        $this->merge([
            'detalle_titulo_tamano' => is_numeric($tamano) ? (int)$tamano : $tamano,
            'detalle_titulo_color' => $this->input('detalle_titulo_color')
                ?? $this->input('titulo_detalle_color')
                ?? $this->input('title_color'),
            'detalle_titulo_estilo' => $style,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    // POST, PATCH, y PUT son todos soportados para actualización
    // POST se usa típicamente con multipart/form-data y permite actualización parcial
    // PUT esperaría todos los campos requeridos
    $isPost = $this->isMethod('post');
    $isPut = $this->isMethod('put');
    $isOnlyPopup = filter_var($this->input('only_popup'), FILTER_VALIDATE_BOOLEAN);

    // Si es solo actualización de popup, los campos base son opcionales (sometimes)
    // De lo contrario, se mantiene la lógica original
    $required = $isOnlyPopup ? 'sometimes' : ($isPut ? 'required' : ($isPost ? 'sometimes' : 'required'));
    $productoId = $this->route('id');

    return [
        'only_popup' => 'nullable|boolean',
        'nombre' => [$required, 'string', 'max:255', Rule::unique('productos', 'nombre')->ignore($productoId)],
        'link' => [$required, 'string', 'max:255', Rule::unique('productos', 'link')->ignore($productoId)],
        'titulo' => [$required, 'string', 'max:255'],
        'subtitulo' => [$required, 'string', 'max:255'],
        'detalle_titulo_tamano' => ['sometimes', 'nullable', 'integer', 'min:8', 'max:200'],
        'detalle_titulo_color' => ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
        'detalle_titulo_estilo' => ['sometimes', 'nullable', 'string', 'in:normal,negrita,cursiva,negrita_cursiva,subrayado'],
        'stock' => [$required, 'integer', 'max:1000', 'min:0'],
        'precio' => [$required, 'numeric', 'min:0'],
        'seccion' => [$required, 'string', 'max:255'],
        'descripcion' => [$required, 'string', 'max:65535'],
        'especificaciones' => ['sometimes', 'nullable', 'string', 'max:65535'],
        'keywords' => ['sometimes', 'nullable', 'string', 'max:65535'],

        // Dimensiones
        'dimensiones' => 'nullable|array',
        'dimensiones.alto' => "nullable|numeric|min:0",
        'dimensiones.largo' => "nullable|numeric|min:0",
        'dimensiones.ancho' => "nullable|numeric|min:0",

        // Metadatos
        'meta_titulo' => 'nullable|string|min:10|max:60',
        'meta_descripcion' => 'nullable|string|min:40|max:160',

        // IMÁGENES DE GALERÍA
        'imagenes_nuevas' => 'nullable|array',
        'imagenes_nuevas.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:3048',
        'imagenes_nuevas_alt' => 'nullable|array',
        'imagenes_nuevas_alt.*' => 'nullable|string|max:255',

        'imagenes_existentes' => 'nullable|array',
        'imagenes_existentes.*.id' => 'nullable|integer|exists:producto_imagenes,id',
        'imagenes_existentes.*.url' => 'nullable|string|max:500',
        'imagenes_existentes.*.alt' => 'nullable|string|max:255',

         // IMÁGENES EDITADAS
        'imagenes_editadas' => 'nullable|array',
        'imagenes_editadas.*.id' => 'nullable|integer|exists:producto_imagenes,id',
        'imagenes_editadas.*.file' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:3048',
        'imagenes_editadas.*.alt' => 'nullable|string|max:255',

        // Imagen popup
        'imagen_popup' => 'nullable|file|image|max:3048',
        'texto_alt_popup' => 'nullable|string|max:255',
        'imagen_popup2' => 'nullable|file|image|max:3048',
        'texto_alt_popup2' => 'nullable|string|max:255',

        // Imagen popup mobile
        'imagen_popup_mobile' => 'nullable|file|image|max:3048',
        'texto_alt_popup_mobile' => 'nullable|string|max:255',
        'imagen_popup_mobile2' => 'nullable|file|image|max:3048',
        'texto_alt_popup_mobile2' => 'nullable|string|max:255',
        //Imagen email

        'imagen_email' => 'nullable|file|image|max:3048',
        'asunto' => 'nullable|string|max:255',
        'mensaje_email' => 'nullable|string',
        'email_btn_text' => 'nullable|string|max:100',
        'email_btn_link' => 'nullable|url|max:255',
        'email_btn_bg_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6})$/',
        'email_btn_text_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6})$/',

        // Imagen Whatsapp
        'imagen_whatsapp' => 'nullable|file|image|max:3048',
        'texto_alt_whatsapp' => 'nullable|string|max:2000',
        'mensaje_whatsapp' => 'nullable|string',

        'video_url' => 'nullable|url|max:500',
        'relacionados' => 'nullable|array',
        'relacionados.*' => 'integer|exists:productos,id',

        // Flags de eliminación de imágenes
        'delete_imagen_popup' => 'nullable|string|in:0,1',
        'delete_imagen_popup2' => 'nullable|string|in:0,1',
        'delete_imagen_popup_2' => 'nullable|string|in:0,1',
        'delete_imagen_popup_mobile' => 'nullable|string|in:0,1',
        'delete_imagen_popup_mobile2' => 'nullable|string|in:0,1',
        'delete_emailImage' => 'nullable|string|in:0,1',
        'delete_whatsappImage' => 'nullable|string|in:0,1',
    ];
 }
}
