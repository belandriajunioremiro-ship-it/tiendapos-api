<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListaPrecioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'segmento'    => $this->segmento,
            'margen_pct'  => (float) $this->margen_pct,
            'activo'      => (bool) $this->activo,
        ];
    }
}
