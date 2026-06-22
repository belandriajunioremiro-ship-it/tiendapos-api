<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetodoPagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'nombre' => $this->nombre,
            'tipo'   => $this->tipo,
            'moneda' => $this->moneda,
            'activo' => (bool) $this->activo,
        ];
    }
}
