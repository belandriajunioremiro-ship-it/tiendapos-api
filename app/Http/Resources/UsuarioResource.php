<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'activo'     => (bool) $this->activo,
            'roles'      => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')),
            'tienda_id'  => $this->tienda_id,
            'created_at' => $this->created_at,
        ];
    }
}
