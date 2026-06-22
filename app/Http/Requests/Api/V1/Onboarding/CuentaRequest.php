<?php
namespace App\Http\Requests\Api\V1\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class CuentaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'pais'                  => 'required|string|size:2|in:VE,CO,MX,EC,AR,PE,CL,BO,UY',
        ];
    }

    public function messages(): array
    {
        return [
            'pais.in' => 'País no soportado. Use: VE, CO, MX, EC, AR, PE, CL, BO, UY',
        ];
    }
}
