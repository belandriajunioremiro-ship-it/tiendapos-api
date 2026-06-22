<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoVentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'metodo_pago_id'  => $this->metodo_pago_id,
            'moneda_pago'     => $this->moneda_pago,
            'monto_pago'      => (float) $this->monto_pago,
            'tasa_aplicada'   => (float) $this->tasa_aplicada,
            'monto_en_moneda_factura' => (float) $this->monto_en_moneda_factura,
            'igtf_pct'        => (float) $this->igtf_pct,
            'igtf_monto'     => (float) $this->igtf_monto,
            'referencia'      => $this->referencia,
            'metodo_pago'     => new MetodoPagoResource($this->whenLoaded('metodoPago')),
        ];
    }
}
