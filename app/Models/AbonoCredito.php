<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AbonoCredito extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'abonos_credito';

    protected $fillable = [ 'tienda_id',
        'factura_credito_id', 'metodo_pago_id', 'sesion_caja_id', 'user_id',
        'moneda_pago', 'monto_pago', 'tasa_usada', 'monto_en_moneda_cta',
        'tasa_base', 'monto_en_base', 'referencia', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'monto_pago'         => 'float',
            'tasa_usada'         => 'float',
            'monto_en_moneda_cta'=> 'float',
            'tasa_base'          => 'float',
            'monto_en_base'      => 'float',
        ];
    }

    public function factura()
    {
        return $this->belongsTo(FacturaCredito::class, 'factura_credito_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function sesionCaja()
    {
        return $this->belongsTo(SesionCaja::class, 'sesion_caja_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

