<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventarioLoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'inventario_id'       => $this->inventario_id,
            'codigo_lote'         => $this->codigo_lote,
            'fecha_vencimiento'   => $this->fecha_vencimiento,
            'cantidad_entrante'   => (float) $this->cantidad_entrante,
            'cantidad_saliente'   => (float) $this->cantidad_saliente,
            'cantidad_disponible' => (float) $this->cantidad_disponible,
            'costo_unitario'      => (float) $this->costo_unitario,
        ];
    }
}
