<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbonoCreditoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'factura_credito_id'   => $this->factura_credito_id,
            'metodo_pago_id'       => $this->metodo_pago_id,
            'moneda_pago'          => $this->moneda_pago,
            'monto_pago'           => (float) $this->monto_pago,
            'tasa_usada'           => (float) $this->tasa_usada,
            'monto_en_moneda_cta'  => (float) $this->monto_en_moneda_cta,
            'tasa_base'            => (float) $this->tasa_base,
            'monto_en_base'        => (float) $this->monto_en_base,
            'referencia'           => $this->referencia,
            'notas'                => $this->notas,
            'metodo_pago'          => new MetodoPagoResource($this->whenLoaded('metodoPago')),
            'factura'              => new FacturaCreditoResource($this->whenLoaded('factura')),
            'creado_en'            => $this->creado_en,
        ];
    }
}
