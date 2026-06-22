<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notificacion extends TiendaPosModel
{

    public $timestamps = false;
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'tienda_id', 'user_id', 'tipo', 'titulo', 'mensaje',
        'referencia_tipo', 'referencia_id', 'datos', 'leida', 'leida_en',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'leida' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function marcarLeida(): void
    {
        $this->update(['leida' => true, 'leida_en' => now()]);
    }
}

