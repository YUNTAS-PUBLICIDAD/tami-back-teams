<?php

namespace App\Http\Requests\PostUser;

use Illuminate\Foundation\Http\FormRequest;

class PostUserUpdate extends FormRequest
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
            'name' => ['required','string','min:2','max:100','regex:/^[\pL\s\-]+$/u'],
            'email' => ['required','email:rfc,dns','max:100','unique:users,email,' . $this->route('id')]
        ];
    }
}
