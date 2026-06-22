<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyPermission(['crear_credito']);
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'required|integer|exists:clientes,id',
            'moneda'     => 'required|string|size:3|exists:monedas,codigo',
            'limite'     => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'moneda.required'     => 'La moneda de la cuenta es obligatoria.',
            'limite.required'    => 'El límite de crédito es obligatorio.',
        ];
    }
}
