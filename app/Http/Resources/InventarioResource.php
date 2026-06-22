<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'variante_id'          => $this->variante_id,
            'almacen_id'           => $this->almacen_id,
            'cantidad_disponible'  => (float) $this->cantidad_disponible,
            'cantidad_reservada'   => (float) $this->cantidad_reservada,
            'costo_promedio'       => (float) $this->costo_promedio,
            'variante'             => new VarianteResource($this->whenLoaded('variante')),
            'almacen'              => new AlmacenResource($this->whenLoaded('almacen')),
            'lotes'               => InventarioLoteResource::collection($this->whenLoaded('lotes')),
        ];
    }
}
