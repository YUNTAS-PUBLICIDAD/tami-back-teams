<?php

namespace App\Http\Requests\PostBlog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PostStoreBlog extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'titulo' => 'required|string|max:255',
            'producto_id' => 'integer|exists:productos,id',
            'link' => 'required|string|max:255|unique:blogs,link',
            'subtitulo1' => 'required|string|max:255',
            'subtitulo2' => 'required|string|max:255',
            'video_url' => 'required|url',
            'video_titulo' => 'required|string|max:2000',
            'created_at' => 'nullable|date',

            'meta_titulo' => 'nullable|string|min:10|',
            'meta_descripcion' => 'nullable|string|min:40|',

            'miniatura' => 'file|image|max:3048',
            'miniatura_nombre' => 'nullable|string|min:10|',
            'miniatura_alt' => 'nullable|string|min:10|',
            'miniatura_tittle' => 'nullable|string|min:10|',
            
            'hero_image' => 'nullable|file|image|max:3048',
            'hero_image_nombre' => 'nullable|string|min:10|',
            'hero_image_alt' => 'nullable|string|min:10|',
            'hero_image_tittle' => 'nullable|string|min:10|',
            
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'required|image|max:3048',
            'img_alt' => 'nullable|array',
            'img_alt.*' => 'nullable|string|',
            'img_nombre' => 'nullable|array',
            'img_nombre.*' => 'nullable|string|',
            'img_tittle' => 'nullable|array',
            'img_tittle.*' => 'nullable|string|',

            'parrafos' => 'required|array',
            'parrafos.*' => 'required|string|max:2047',
            'popup_button_text' => 'nullable|string|',
            'popup_button_color' => 'nullable|string|',
            'popup_text_color' => 'nullable|string|',
            /**cambios en caracteres */
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
