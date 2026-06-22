<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'nombre'           => $this->nombre,
            'nombre_comercial' => $this->nombre_comercial,
            'rif'              => $this->rif,
            'telefono'         => $this->telefono,
            'email'            => $this->email,
            'direccion'        => $this->direccion,
            'activo'           => (bool) $this->activo,
        ];
    }
}
