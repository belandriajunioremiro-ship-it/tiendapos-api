<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenCompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'proveedor_id'   => $this->proveedor_id,
            'almacen_id'     => $this->almacen_id,
            'codigo_orden'   => $this->codigo_orden,
            'moneda'         => $this->moneda,
            'tasa_cambio'    => (float) $this->tasa_cambio,
            'subtotal'       => (float) $this->subtotal,
            'impuesto_total' => (float) $this->impuesto_total,
            'total'          => (float) $this->total,
            'estado'         => $this->estado,
            'notas'          => $this->notas,
            'proveedor'      => new ProveedorResource($this->whenLoaded('proveedor')),
            'items'          => ItemOrdenCompraResource::collection($this->whenLoaded('items')),
            'creado_en'      => $this->creado_en,
        ];
    }
}
