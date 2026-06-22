<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuentaCreditoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'cliente_id'       => $this->cliente_id,
            'moneda'           => $this->moneda,
            'limite'           => (float) $this->limite,
            'saldo_usado'      => (float) $this->saldo_usado,
            'saldo_disponible' => (float) $this->saldo_disponible,
            'estado'           => $this->estado,
            'cliente'          => new ClienteResource($this->whenLoaded('cliente')),
            'facturas'         => FacturaCreditoResource::collection($this->whenLoaded('facturas')),
            'actualizado_en'   => $this->actualizado_en,
        ];
    }
}
