<?php
namespace App\Http\Requests\Api\V1\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class PrimerProductoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'          => 'required|string|max:200',
            'sku'             => 'nullable|string|max:50|unique:productos,codigo_sku',
            'codigo_barra'    => 'nullable|string|max:60|unique:variantes_producto,codigo_barra',
            'categoria_id'    => 'nullable|exists:categorias_productos,id',
            'costo'           => 'nullable|numeric|min:0',
            'aplica_iva'      => 'boolean',
            'stock_inicial'   => 'nullable|numeric|min:0',
            'descripcion'     => 'nullable|string',
        ];
    }
}
