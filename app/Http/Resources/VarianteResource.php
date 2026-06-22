<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VarianteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'producto_id'       => $this->producto_id,
            'sku_variante'      => $this->sku_variante,
            'codigo_barra'      => $this->codigo_barra,
            'atributo_valores'  => $this->atributo_valores,
            'precio'            => (float) $this->precio,
            'costo'             => (float) $this->costo,
            'controla_lotes'    => (bool) $this->controla_lotes,
            'controla_vencimiento' => (bool) $this->controla_vencimiento,
            'peso'              => $this->peso ? (float) $this->peso : null,
            'volumen'           => $this->volumen ? (float) $this->volumen : null,
            'activo'            => (bool) $this->activo,
            'producto'         => new ProductoResource($this->whenLoaded('producto')),
        ];
    }
}
