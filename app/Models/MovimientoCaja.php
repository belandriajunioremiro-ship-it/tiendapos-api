<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MovimientoCaja extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'movimientos_caja';

    protected $fillable = [ 'tienda_id',
        'sesion_id', 'user_id', 'tipo', 'moneda', 'monto',
        'tasa_base', 'monto_en_base', 'concepto', 'referencia',
    ];

    protected function casts(): array
    {
        return [
            'monto'        => 'float',
            'tasa_base'    => 'float',
            'monto_en_base'=> 'float',
        ];
    }

    public function sesion()
    {
        return $this->belongsTo(SesionCaja::class, 'sesion_id');
    }
}

