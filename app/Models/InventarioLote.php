<?php

namespace App\Models;

use App\Traits\BelongsToTienda;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioLote extends TiendaPosModel
{

    public $timestamps = false;
    use BelongsToTienda;

    protected $table = 'inventario_lotes';

    protected $fillable = [ 'tienda_id',
        'inventario_id',
        'lote',
        'fecha_vencimiento',
        'cantidad',
        'costo_unitario',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'cantidad'          => 'decimal:4',
        'costo_unitario'    => 'decimal:6',
        'creado_en'         => 'datetime',
        'actualizado_en'    => 'datetime',
    ];

    // ──────────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────────
    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    /** Solo lotes con stock disponible (excluye agotados para FEFO) */
    public function scopeConStock($query)
    {
        return $query->where('cantidad', '>', 0);
    }

    /** Orden FEFO: primero el lote que vence antes */
    public function scopeFefo($query)
    {
        return $query->conStock()->orderBy('fecha_vencimiento', 'asc');
    }

    /** Próximos a vencer (para alertas) */
    public function scopePorVencer($query, int $dias = 60)
    {
        return $query->conStock()
            ->where('fecha_vencimiento', '<=', now()->addDays($dias))
            ->orderBy('fecha_vencimiento', 'asc');
    }

    /** Ya vencidos (deberían estar en 0, pero por si acaso) */
    public function scopeVencidos($query)
    {
        return $query->where('fecha_vencimiento', '<', now()->toDateString());
    }

    // ──────────────────────────────────────────────
    // Helpers de negocio
    // ──────────────────────────────────────────────

    public function getDiasParaVencerAttribute(): int
    {
        return now()->startOfDay()->diffInDays($this->fecha_vencimiento, false);
    }

    public function getEstaVencidoAttribute(): bool
    {
        return $this->fecha_vencimiento < now()->toDateString();
    }

    public function getEstaPorVencerAttribute(): bool
    {
        return !$this->esta_vencido && $this->dias_para_vencer <= 60;
    }

    /** Descuenta stock del lote (no baja de 0) */
    public function descontar(float $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new \DomainException('La cantidad a descontar debe ser positiva.');
        }
        if ($cantidad > $this->cantidad) {
            throw new \DomainException(
                "Stock insuficiente en lote {$this->lote}. Disponible: {$this->cantidad}, solicitado: {$cantidad}"
            );
        }

        $this->decrement('cantidad', $cantidad);
    }

    /** Suma stock al lote (recepción de compra) */
    public function incrementar(float $cantidad, ?float $nuevoCosto = null): void
    {
        if ($cantidad <= 0) {
            throw new \DomainException('La cantidad a ingresar debe ser positiva.');
        }

        $this->increment('cantidad', $cantidad);

        if ($nuevoCosto !== null && $nuevoCosto > 0) {
            $this->update(['costo_unitario' => $nuevoCosto]);
        }
    }
}
