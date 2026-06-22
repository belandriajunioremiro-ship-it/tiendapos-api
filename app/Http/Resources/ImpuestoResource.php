<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImpuestoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nombre'     => $this->nombre,
            'porcentaje' => (float) $this->porcentaje,
            'tipo'       => $this->tipo,
            'is_defecto'  => (bool) $this->is_defecto,
            'activo'     => (bool) $this->activo,
        ];
    }
}
