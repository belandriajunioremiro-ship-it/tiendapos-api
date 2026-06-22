<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TasaCambioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'moneda_base'     => $this->moneda_base,
            'moneda_destino'  => $this->moneda_destino,
            'tasa'            => (float) $this->tasa,
            'fuente'          => $this->fuente,
            'fecha'           => $this->fecha,
            'activa'          => (bool) $this->activa,
        ];
    }
}
