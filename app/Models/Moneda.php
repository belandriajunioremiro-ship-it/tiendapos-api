<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Moneda extends Model
{
    protected $table = 'monedas';

    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'nombre',
        'simbolo',
        'decimales',
        'es_cripto',
        'activa',
    ];

    protected $casts = [
        'decimales' => 'integer',
        'es_cripto' => 'boolean',
        'activa'    => 'boolean',
    ];

    public $timestamps = true;

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
