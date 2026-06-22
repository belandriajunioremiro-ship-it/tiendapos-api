<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'tipo_documento'    => $this->tipo_documento,
            'numero_documento'  => $this->numero_documento,
            'nombre'            => $this->nombre,
            'nombre_comercial'  => $this->nombre_comercial,
            'telefono'          => $this->telefono,
            'telefono2'         => $this->telefono2,
            'email'             => $this->email,
            'direccion'         => $this->direccion,
            'tipo_cliente'      => $this->tipo_cliente,
            'moneda_credito'    => $this->moneda_credito,
            'limite_credito'    => (float) $this->limite_credito,
            'dias_credito'      => $this->dias_credito,
            'activo'            => (bool) $this->activo,
            'lista_precio'     => new ListaPrecioResource($this->whenLoaded('listaPrecio')),
            'cuenta_credito'   => new CuentaCreditoResource($this->whenLoaded('cuentaCredito')),
            'creado_en'        => $this->creado_en,
        ];
    }
}
