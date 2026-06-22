<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacturaProveedor extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'facturas_proveedor';

    protected $fillable = [ 'tienda_id',
        'orden_id', 'proveedor_id', 'numero_factura', 'moneda', 'subtotal',
        'impuesto', 'total', 'tasa_base', 'total_en_base',
        'fecha_factura', 'fecha_vence', 'estado', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'      => 'float',
            'impuesto'      => 'float',
            'total'         => 'float',
            'tasa_base'     => 'float',
            'total_en_base' => 'float',
        ];
    }

    public function orden()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}

