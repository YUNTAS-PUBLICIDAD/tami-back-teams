<?php

namespace App\Http\Requests\PostBlog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateBlog extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Cambia según tu lógica de permisos
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /* $isPut = $this->isMethod('put');
        $required = $isPut ? 'required' : 'sometimes'; */
        $blogId = $this->route('blog');

        return [
            /* 'titulo' => [$required, 'string', 'max:255'],
            'producto_id' => [$required, 'integer', 'exists:productos,id'],
            'link' => [$required, 'string', 'max:255', Rule::unique('blogs', 'link')->ignore($blogId)],
            'subtitulo1' => [$required, 'string', 'max:255'],
            'subtitulo2' => [$required, 'string', 'max:255'],
            'video_url' => [$required, 'url'],
            'video_titulo' => [$required, 'string', 'max:255'], */
            'titulo' => ['required', 'string', 'max:255'],
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'link' => ['required', 'string', 'max:255', Rule::unique('blogs', 'link')->ignore($blogId)],
            'subtitulo1' => ['required', 'string', 'max:255'],
            'subtitulo2' => ['required', 'string', 'max:255'],
            'video_url' => ['required', 'url'],
            'video_titulo' => ['required', 'string', 'max:255'],

            'meta_titulo' => 'nullable|string|min:10|max:60',
            'meta_descripcion' => 'nullable|string|min:40|max:160',

            'miniatura' => ['sometimes', 'image', 'max:3048'],
            'imagenes' => ['sometimes', 'array'],
            'imagenes.*' => ['sometimes', 'image', 'max:3048'],

            /* 'text_alt' => [$isPut ? 'required' : 'sometimes', 'array'],
            'text_alt.*' => [$isPut ? 'required' : 'sometimes', 'string', 'max:255'],

            'parrafos' => [$isPut ? 'required' : 'sometimes', 'array'],
            'parrafos.*' => [$isPut ? 'required' : 'sometimes', 'string', 'max:2047'], */

            'text_alt' => ['required', 'array'],
            'text_alt.*' => ['required', 'string', 'max:255'],

            'parrafos' => ['required', 'array'],
            'parrafos.*' => ['required', 'string', 'max:2047'],
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
