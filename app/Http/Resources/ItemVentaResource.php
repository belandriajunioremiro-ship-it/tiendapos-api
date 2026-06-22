<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemVentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'variante_id'         => $this->variante_id,
            'cantidad'            => (float) $this->cantidad,
            'precio_unitario'    => (float) $this->precio_unitario,
            'tasa_conversion'    => (float) $this->tasa_conversion,
            'precio_en_factura'  => (float) $this->precio_en_factura,
            'descuento_pct'      => (float) $this->descuento_pct,
            'impuesto_pct'       => (float) $this->impuesto_pct,
            'impuesto_monto'     => (float) $this->impuesto_monto,
            'subtotal'           => (float) $this->subtotal,
            'costo_unitario'     => (float) $this->costo_unitario,
            'variante'           => new VarianteResource($this->whenLoaded('variante')),
        ];
    }
}
