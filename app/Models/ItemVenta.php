<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemVenta extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'items_venta';
protected $fillable = [ 'tienda_id',
        'venta_id',
        'variante_id',
        'cantidad',
        'moneda_precio',
        'precio_unitario',
        'costo_unitario',
        'tasa_conversion',
        'precio_en_factura',
        'costo_en_factura',
        'margen_aplicado',
        'descuento_pct',
        'impuesto_pct',
        'impuesto_monto',
        'total_linea',
        'ganancia_linea'
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'float',
            'precio_unitario' => 'float',
            'costo_unitario' => 'float',
            'tasa_conversion' => 'float',
            'precio_en_factura' => 'float',
            'costo_en_factura' => 'float',
            'margen_aplicado' => 'float',
            'descuento_pct' => 'float',
            'impuesto_pct' => 'float',
            'impuesto_monto' => 'float',
            'total_linea' => 'float',
            'ganancia_linea' => 'float',
        ];
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_id');
    }
}

