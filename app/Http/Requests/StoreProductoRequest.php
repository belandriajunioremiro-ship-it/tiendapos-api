<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyPermission(['crear_productos']);
    }

    public function rules(): array
    {
        return [
            'categoria_id'          => 'required|integer|exists:categorias_productos,id',
            'unidad_id'             => 'required|integer|exists:unidades,id',
            'impuesto_id'           => 'nullable|integer|exists:impuestos,id',
            'margen_id'             => 'nullable|integer|exists:margenes_ganancia,id',
            'moneda_precio'         => 'required|string|size:3',
            'codigo_sku'            => 'required|string|max:50|unique:productos,codigo_sku',
            'nombre'                => 'required|string|max:200',
            'descripcion'           => 'nullable|string',
            'foto_url'              => 'nullable|url|max:500',
            'referencia_interna'    => 'nullable|string|max:80',
            'costo_promedio'        => 'required|numeric|min:0',
            'margen_pct'            => 'required|numeric|min:0|max:9999',
            'precio_minimo'         => 'nullable|numeric|min:0',
            'permite_precio_manual' => 'sometimes|boolean',
            'aplica_descuento'      => 'sometimes|boolean',
            'atributos'             => 'sometimes|array',
            'activo'                => 'sometimes|boolean',
            'variante_codigo_barra' => 'nullable|string|max:60|unique:variantes_producto,codigo_barra',
            'variante_descripcion'  => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_sku.required'     => 'El código SKU es obligatorio.',
            'codigo_sku.unique'       => 'Este código SKU ya existe.',
            'nombre.required'        => 'El nombre del producto es obligatorio.',
            'categoria_id.required'  => 'Debe seleccionar una categoría.',
            'unidad_id.required'     => 'Debe seleccionar una unidad.',
            'costo_promedio.required' => 'El costo promedio es obligatorio.',
            'margen_pct.required'    => 'El margen de ganancia es obligatorio.',
        ];
    }
}
