<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CajaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,
            'activo'      => (bool) $this->activo,
            'sesiones'    => SesionCajaResource::collection($this->whenLoaded('sesiones')),
        ];
    }
}
