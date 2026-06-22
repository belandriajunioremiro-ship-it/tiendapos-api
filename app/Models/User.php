<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    protected $fillable = [
        'tienda_id',
        'name',
        'email',
        'password',
        'activo',
        'ultimo_login',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'activo'            => 'boolean',
            'ultimo_login'      => 'datetime',
        ];
    }

    public function tienda(): BelongsTo
    {
        return $this->belongsTo(Tienda::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'user_id');
    }

    public function sesionesCaja(): HasMany
    {
        return $this->hasMany(SesionCaja::class, 'user_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeByTienda($query, int $tiendaId)
    {
        return $query->where('tienda_id', $tiendaId);
    }

    public function esAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function esCajero(): bool
    {
        return $this->hasRole('cajero');
    }


}
