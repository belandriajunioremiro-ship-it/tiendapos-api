<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => 'required|email|max:150',
            'password'    => 'required|string|min:6|max:100',
            'device_name' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'El email es obligatorio.',
            'email.email'       => 'El email no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }
}
