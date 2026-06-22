<?php
namespace App\Http\Requests\Api\V1\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class ConfigurarNegocioRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tipo_negocio'    => 'required|string|in:farmacia,ferreteria,bodega,restaurante,licoreria,abarrotes,ropa,motos,general',
            'nombre_almacen'  => 'nullable|string|max:100',
            'nombre_caja'     => 'nullable|string|max:100',
            'tipo_impresora'  => 'nullable|string|in:termica_58mm,termica_80mm,a4,pdf,ninguno',
        ];
    }
}
