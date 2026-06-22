<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnidadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'nombre'            => $this->nombre,
            'abreviatura'       => $this->abreviatura,
            'tipo'              => $this->tipo,
            'factor_conversion' => (float) $this->factor_conversion,
            'unidad_base_id'   => $this->unidad_base_id,
        ];
    }
}
