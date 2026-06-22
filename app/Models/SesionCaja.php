<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SesionCaja extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    const CREATED_AT = 'apertura_en';

    protected $table = 'sesiones_caja';
    protected $fillable = [ 'tienda_id',
        'caja_id', 'user_id', 'estado', 'observaciones', 'apertura_en', 'cierre_en',
        'total_ventas_base', 'total_devoluciones_base', 'total_retiros_base',
        'total_ingresos_base', 'total_gastos_base', 'diferencia_base',
    ];

    protected function casts(): array
    {
        return [
            'total_ventas_base'       => 'float',
            'total_devoluciones_base' => 'float',
            'total_retiros_base'      => 'float',
            'total_ingresos_base'     => 'float',
            'total_gastos_base'       => 'float',
            'diferencia_base'         => 'float',
        ];
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'sesion_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'sesion_caja_id');
    }
}

