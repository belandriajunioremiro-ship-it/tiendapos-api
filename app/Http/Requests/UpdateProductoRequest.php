<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyPermission(['editar_productos']);
    }

    public function rules(): array
    {
        $id = $this->route('producto') ?? $this->route('id');

        return [
            'categoria_id'          => 'sometimes|integer|exists:categorias_productos,id',
            'unidad_id'             => 'sometimes|integer|exists:unidades,id',
            'impuesto_id'           => 'nullable|integer|exists:impuestos,id',
            'margen_id'             => 'nullable|integer|exists:margenes_ganancia,id',
            'moneda_precio'         => 'sometimes|string|size:3',
            'codigo_sku'            => 'sometimes|string|max:50|unique:productos,codigo_sku,' . $id,
            'nombre'                => 'sometimes|string|max:200',
            'descripcion'           => 'nullable|string',
            'foto_url'              => 'nullable|url|max:500',
            'referencia_interna'    => 'nullable|string|max:80',
            'costo_promedio'        => 'sometimes|numeric|min:0',
            'margen_pct'            => 'sometimes|numeric|min:0|max:9999',
            'precio_minimo'         => 'nullable|numeric|min:0',
            'permite_precio_manual' => 'sometimes|boolean',
            'aplica_descuento'      => 'sometimes|boolean',
            'atributos'             => 'sometimes|array',
            'activo'                => 'sometimes|boolean',
        ];
    }
}
