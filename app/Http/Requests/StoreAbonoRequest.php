<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbonoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyPermission(['registrar_abono']);
    }

    public function rules(): array
    {
        return [
            'factura_credito_id' => 'required|integer|exists:facturas_credito,id',
            'metodo_pago_id'     => 'required|integer|exists:metodos_pago,id',
            'sesion_caja_id'     => 'nullable|integer|exists:sesiones_caja,id',
            'moneda_pago'        => 'required|string|size:3',
            'monto_pago'         => 'required|numeric|min:0.01',
            'tasa_usada'         => 'nullable|numeric|min:0',
            'monto_en_moneda_cta' => 'required|numeric|min:0.01',
            'tasa_base'          => 'nullable|numeric|min:0',
            'monto_en_base'      => 'nullable|numeric|min:0',
            'referencia'         => 'nullable|string|max:100',
            'notas'              => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'factura_credito_id.required' => 'Debe indicar la factura a abonar.',
            'metodo_pago_id.required'     => 'Debe seleccionar un método de pago.',
            'monto_pago.required'         => 'El monto del pago es obligatorio.',
            'monto_en_moneda_cta.required' => 'El monto en moneda de la cuenta es obligatorio.',
        ];
    }
}
