<?php

namespace App\Http\Requests\PostUser;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;

class PostUser extends FormRequest
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
        return [
            'name' => 'required|string|unique:users,name|min:2|max:100',
            'email' => 'required|email|unique:users,email|min:15|max:100',
            'celular' => 'required|string|unique:users,celular|regex:/^[0-9]{9}$/',
            //No se pide en la pagina de administraciion, añadir usuario, por lo que se le agrega un valor por defecto / password
            //'password' => 'string|min:8|max:255'
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
