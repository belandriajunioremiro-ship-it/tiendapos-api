<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacturaCreditoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'venta_id'          => $this->venta_id,
            'cliente_id'        => $this->cliente_id,
            'cuenta_credito_id' => $this->cuenta_credito_id,
            'moneda'            => $this->moneda,
            'monto_total'       => (float) $this->monto_total,
            'saldo_pendiente'   => (float) $this->saldo_pendiente,
            'dias_plazo'        => $this->dias_plazo,
            'fecha_emision'     => $this->fecha_emision,
            'fecha_vence'       => $this->fecha_vence,
            'estado'            => $this->estado,
            'venta'             => new VentaResource($this->whenLoaded('venta')),
            'abonos'            => AbonoCreditoResource::collection($this->whenLoaded('abonos')),
            'creado_en'         => $this->creado_en,
        ];
    }
}
