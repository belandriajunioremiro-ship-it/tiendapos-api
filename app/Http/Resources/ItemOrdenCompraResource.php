<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemOrdenCompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'variante_id'     => $this->variante_id,
            'cantidad'        => (float) $this->cantidad,
            'cantidad_recibida' => (float) $this->cantidad_recibida,
            'precio_unitario' => (float) $this->precio_unitario,
            'subtotal'        => (float) $this->subtotal,
        ];
    }
}
