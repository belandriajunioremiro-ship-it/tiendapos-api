<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrdenCompra extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'ordenes_compra';
    protected $fillable = [ 'tienda_id',
        'proveedor_id', 'almacen_id', 'user_id', 'aprobado_por', 'numero',
        'moneda', 'subtotal', 'impuesto', 'total', 'tasa_base', 'total_en_base',
        'estado', 'fecha_estimada', 'notas', 'aprobado_en',
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

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function items()
    {
        return $this->hasMany(ItemOrdenCompra::class, 'orden_id');
    }
}

