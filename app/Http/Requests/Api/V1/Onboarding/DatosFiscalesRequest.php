<?php
namespace App\Http\Requests\Api\V1\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class DatosFiscalesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'identificacion_fiscal' => 'required|string|max:20',
            'razon_social'          => 'required|string|max:200',
            'nombre_comercial'      => 'nullable|string|max:200',
            'direccion'             => 'required|string',
            'telefono'              => 'nullable|string|max:20',
            'email'                 => 'nullable|email|max:150',
            'regimen_fiscal'        => 'nullable|string|max:100',
            'actividad_economica'   => 'nullable|string|max:200',
            'codigo_postal'         => 'nullable|string|max:20',
            'logo_url'              => 'nullable|string|max:500',
        ];
    }
}
