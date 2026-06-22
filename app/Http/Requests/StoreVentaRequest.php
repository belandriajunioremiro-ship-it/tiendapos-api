<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // Cabecera de venta
            'cliente_id'      => 'required|integer|exists:clientes,id',
            'caja_id'         => 'required|integer|exists:cajas,id',
            'sesion_caja_id'  => 'nullable|integer|exists:sesiones_caja,id',
            'almacen_id'      => 'required|integer|exists:almacenes,id',
            'moneda_factura'  => 'required|string|size:3',
            'tipo_documento'  => 'sometimes|string|in:FAC,NE,NC,ND,COT',
            'tipo_pago'       => 'sometimes|string|in:contado,credito',
            'fuente_tasa'     => 'nullable|string|in:BCV,paralelo,api_automatica,manual',
            'notas'           => 'nullable|string',

            // Items del carrito
            'items'                     => 'required|array|min:1',
            'items.*.variante_id'       => 'required|integer|exists:variantes_producto,id',
            'items.*.cantidad'          => 'required|numeric|min:0.0001',
            'items.*.precio_unitario'   => 'required|numeric|min:0',
            'items.*.tasa_conversion'   => 'required|numeric|min:0',
            'items.*.descuento_pct'     => 'sometimes|numeric|min:0|max:100',
            'items.*.impuesto_pct'      => 'sometimes|numeric|min:0',

            // Pagos recibidos
            'pagos'                     => 'required|array|min:1',
            'pagos.*.metodo_pago_id'    => 'required|integer|exists:metodos_pago,id',
            'pagos.*.monto_pago'        => 'required|numeric|min:0.0001',
            'pagos.*.tasa_aplicada'     => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required'         => 'Debe seleccionar un cliente.',
            'caja_id.required'            => 'Debe seleccionar una caja.',
            'almacen_id.required'         => 'Debe seleccionar un almacén.',
            'moneda_factura.required'     => 'Debe indicar la moneda de facturación.',
            'items.required'              => 'El carrito no puede estar vacío.',
            'items.min'                   => 'Debe agregar al menos un producto.',
            'pagos.required'              => 'Debe registrar al menos un pago.',
            'items.*.variante_id.exists'  => 'Una o más variantes no existen.',
            'pagos.*.metodo_pago_id.exists' => 'Uno o más métodos de pago no son válidos.',
        ];
    }
}
