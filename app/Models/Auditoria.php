<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Auditoria extends TiendaPosModel
{

    public $timestamps = false;
    use HasFactory;

    protected $table = 'auditoria';

    protected $fillable = [
        'user_id', 'accion', 'tabla', 'registro_id',
        'datos_antes', 'datos_despues', 'ip', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'datos_antes'   => 'array',
            'datos_despues' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

