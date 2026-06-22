<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CambiarPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_actual'  => 'required|string',
            'password_nueva'   => 'required|string|min:8|max:100|confirmed|different:password_actual',
        ];
    }

    public function messages(): array
    {
        return [
            'password_nueva.min'       => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password_nueva.confirmed' => 'La confirmación no coincide.',
            'password_nueva.different' => 'La nueva contraseña debe ser diferente a la actual.',
        ];
    }
}
