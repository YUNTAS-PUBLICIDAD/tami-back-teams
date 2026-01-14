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

            'meta_titulo' => 'nullable|string|min:10|max:60',
            'meta_descripcion' => 'nullable|string|min:40|max:160',

            'miniatura' => 'file|image|max:3048',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'required|image|max:3048',

            'text_alt' => 'required|array',
            'text_alt.*' => 'required|string|max:255',

            'parrafos' => 'required|array',
            'parrafos.*' => 'required|string|max:2047',
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
