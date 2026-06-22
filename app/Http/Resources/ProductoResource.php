<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'nombre'         => $this->nombre,
            'codigo_sku'     => $this->codigo_sku,
            'codigo_barra'   => $this->codigo_barra,
            'descripcion'    => $this->descripcion,
            'tipo'           => $this->tipo,
            'moneda_precio'  => $this->moneda_precio,
            'precio_base'    => (float) $this->precio_base,
            'costo'          => (float) $this->costo,
            'activo'         => (bool) $this->activo,
            'categoria'      => new CategoriaResource($this->whenLoaded('categoria')),
            'unidad'         => new UnidadResource($this->whenLoaded('unidad')),
            'variantes'      => VarianteResource::collection($this->whenLoaded('variantes')),
            'impuesto'       => new ImpuestoResource($this->whenLoaded('impuesto')),
            'creado_en'      => $this->creado_en,
        ];
    }
}
