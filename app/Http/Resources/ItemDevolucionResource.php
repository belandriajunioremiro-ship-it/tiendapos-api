<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemDevolucionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'item_venta_id'             => $this->item_venta_id,
            'variante_id'               => $this->variante_id,
            'cantidad'                  => (float) $this->cantidad,
            'precio_unitario'           => (float) $this->precio_unitario,
            'monto_devuelto_en_factura' => (float) $this->monto_devuelto_en_factura,
            'impuesto_monto'            => (float) $this->impuesto_monto,
            'total_linea'               => (float) $this->total_linea,
            'motivo'                    => $this->motivo,
            'item_venta'               => new ItemVentaResource($this->whenLoaded('itemVenta')),
        ];
    }
}
