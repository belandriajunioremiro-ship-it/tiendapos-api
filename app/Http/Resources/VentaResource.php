<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'numero_factura'   => $this->numero_factura,
            'tipo_documento'   => $this->tipo_documento,
            'tipo_pago'        => $this->tipo_pago,
            'moneda_factura'   => $this->moneda_factura,
            'subtotal'         => (float) $this->subtotal,
            'descuento'        => (float) $this->descuento,
            'impuesto_iva'     => (float) $this->impuesto_iva,
            'impuesto_igtf'    => (float) $this->impuesto_igtf,
            'total'            => (float) $this->total,
            'tasa_base_usada'  => (float) $this->tasa_base_usada,
            'total_en_base'    => (float) $this->total_en_base,
            'estado'           => $this->estado,
            'notas'            => $this->notas,
            'cliente'          => new ClienteResource($this->whenLoaded('cliente')),
            'caja'             => new CajaResource($this->whenLoaded('caja')),
            'items'            => ItemVentaResource::collection($this->whenLoaded('items')),
            'pagos'            => PagoVentaResource::collection($this->whenLoaded('pagos')),
            'creado_en'        => $this->creado_en,
        ];
    }
}
