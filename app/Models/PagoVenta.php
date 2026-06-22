<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PagoVenta extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'pagos_venta';
protected $fillable = [ 'tienda_id',
        'venta_id',
        'metodo_pago_id',
        'sesion_caja_id',
        'moneda_pago',
        'monto_pago',
        'tasa_aplicada',
        'monto_en_factura',
        'monto_igtf',
        'tasa_igtf_pct',
        'monto_igtf_en_factura',
        'referencia',
        'banco'
    ];

    protected function casts(): array
    {
        return [
            'monto_pago' => 'float',
            'tasa_aplicada' => 'float',
            'monto_en_factura' => 'float',
            'monto_igtf' => 'float',
            'tasa_igtf_pct' => 'float',
            'monto_igtf_en_factura' => 'float',
        ];
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
}

