<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MetodoPago extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda, HasFactory;

    protected $table = 'metodos_pago';
protected $fillable = [ 'tienda_id',
        'nombre',
        'tipo',
        'moneda',
        'requiere_referencia',
        'requiere_banco',
        'grava_igtf',
        'activo'
    ];

    protected function casts(): array
    {
        return [
            'requiere_referencia' => 'boolean',
            'requiere_banco' => 'boolean',
            'grava_igtf' => 'boolean',
            'activo' => 'boolean',
        ];
    }
}

