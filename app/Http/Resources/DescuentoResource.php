<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DescuentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'nombre'           => $this->nombre,
            'tipo_aplicacion'  => $this->tipo_aplicacion,
            'producto_id'      => $this->producto_id,
            'categoria_id'     => $this->categoria_id,
            'cliente_id'       => $this->cliente_id,
            'valor_pct'        => (float) $this->valor_pct,
            'maximo_pct'       => (float) $this->maximo_pct,
            'fecha_inicio'    => $this->fecha_inicio,
            'fecha_fin'       => $this->fecha_fin,
            'activo'           => (bool) $this->activo,
        ];
    }
}
