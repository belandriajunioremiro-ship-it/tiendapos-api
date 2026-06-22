<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'nombre'    => $this->nombre,
            'slug'      => $this->slug,
            'padre_id'  => $this->padre_id,
            'activo'    => (bool) $this->activo,
            'padre'     => new CategoriaResource($this->whenLoaded('padre')),
            'hijos'     => CategoriaResource::collection($this->whenLoaded('hijos')),
        ];
    }
}
