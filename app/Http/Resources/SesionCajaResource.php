<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SesionCajaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'caja_id'          => $this->caja_id,
            'user_id'          => $this->user_id,
            'monto_apertura'   => (float) $this->monto_apertura,
            'monto_cierre'     => $this->monto_cierre ? (float) $this->monto_cierre : null,
            'moneda'           => $this->moneda,
            'fecha_apertura'   => $this->fecha_apertura,
            'fecha_cierre'     => $this->fecha_cierre,
            'estado'           => $this->estado,
            'caja'             => new CajaResource($this->whenLoaded('caja')),
        ];
    }
}
