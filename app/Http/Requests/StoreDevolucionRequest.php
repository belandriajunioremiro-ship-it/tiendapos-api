<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDevolucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyPermission(['crear_devolucion']);
    }

    public function rules(): array
    {
        return [
            'venta_id'          => 'required|integer|exists:ventas,id',
            'almacen_id'        => 'required|integer|exists:almacenes,id',
            'motivo'            => 'required|string|max:100',
            'notas'             => 'nullable|string',
            'moneda'            => 'required|string|size:3',
            'tipo_reintegro'    => 'required|string|in:efectivo,nota_credito,cambio_producto,abono_cuenta',
            'items'             => 'required|array|min:1',
            'items.*.item_venta_id' => 'required|integer|exists:items_venta,id',
            'items.*.cantidad'     => 'required|numeric|min:0.0001',
        ];
    }

    public function messages(): array
    {
        return [
            'venta_id.required'       => 'Debe indicar la venta a devolver.',
            'almacen_id.required'     => 'Debe seleccionar un almacén para el reingreso.',
            'motivo.required'         => 'El motivo de la devolución es obligatorio.',
            'tipo_reintegro.required' => 'Debe indicar el tipo de reintegro.',
            'items.required'          => 'Debe incluir al menos un item a devolver.',
            'items.min'               => 'Debe incluir al menos un item a devolver.',
        ];
    }
}
