<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevolucionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'venta_id'             => $this->venta_id,
            'user_id'              => $this->user_id,
            'almacen_id'           => $this->almacen_id,
            'numero_nota_credito'  => $this->numero_nota_credito,
            'motivo'               => $this->motivo,
            'descripcion'          => $this->descripcion,
            'moneda_devolucion'    => $this->moneda_devolucion,
            'tipo_reintegro'       => $this->tipo_reintegro,
            'estado'               => $this->estado,
            'total_devuelto'       => (float) $this->total_devuelto,
            'venta'                => new VentaResource($this->whenLoaded('venta')),
            'items'                => ItemDevolucionResource::collection($this->whenLoaded('items')),
            'creado_en'            => $this->creado_en,
        ];
    }
}
