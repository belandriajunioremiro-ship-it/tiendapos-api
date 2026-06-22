<?php

namespace App\Services;

use App\Exceptions\Suscripcion\LimitePlanExcedidoException;
use App\Exceptions\Suscripcion\SuscripcionVencidaException;
use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Plane;
use App\Models\Producto;
use App\Models\Suscripcion;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SuscripcionService
{
    /**
     * Verifica si la suscripción está activa y no ha vencido.
     * Lanza excepción si está vencida o suspendida.
     */
    public function verificarActiva(int $tiendaId): void
    {
        $suscripcion = $this->obtenerActiva($tiendaId);

        if ($suscripcion->estado === 'suspendida') {
            throw SuscripcionVencidaException::suspendada();
        }

        if ($suscripcion->estado === 'vencida') {
            throw SuscripcionVencidaException::trialVencido(
                $suscripcion->fin_trial?->format('d/m/Y') ?? 'N/A'
            );
        }

        // Verificar si el trial ha vencido
        if ($suscripcion->estado === 'trial' && $suscripcion->fin_trial && now()->isAfter($suscripcion->fin_trial)) {
            // Actualizar estado a vencida
            $suscripcion->update(['estado' => 'vencida']);
            throw SuscripcionVencidaException::trialVencido(
                $suscripcion->fin_trial->format('d/m/Y')
            );
        }
    }

    /**
     * Verifica el acceso del usuario a la plataforma.
     * Método usado por el middleware CheckSuscripcionActiva.
     */
    public function verificarAcceso(int $tiendaId): void
    {
        $this->verificarActiva($tiendaId);
    }

    /**
     * Marca como vencidas todas las suscripciones trial que han expirado.
     * Ejecutado diariamente por el comando programado.
     */
    public function marcarTrialsVencidos(): int
    {
        return Suscripcion::where('estado', 'trial')
            ->where('fin_trial', '<', now())
            ->update(['estado' => 'vencida']);
    }

    /**
     * Cancela una suscripción.
     */
    public function cancelarSuscripcion(int $tiendaId, int $userId, ?string $motivo = null): void
    {
        Suscripcion::where('tienda_id', $tiendaId)
            ->whereIn('estado', ['trial', 'activa', 'vencida'])
            ->update([
                'estado'             => 'cancelada',
                'cancelado_en'       => now(),
                'cancelado_por'      => $userId,
                'motivo_cancelacion' => $motivo,
                'auto_renovar'       => false,
            ]);
    }

    /**
     * Obtiene la suscripción activa de una tienda.
     */
    public function obtenerActiva(int $tiendaId): Suscripcion
    {
        return Suscripcion::where('tienda_id', $tiendaId)
            ->whereIn('estado', ['trial', 'activa', 'vencida', 'suspendida'])
            ->latest()
            ->firstOrFail();
    }

    /**
     * Valida que la tienda no haya excedido el límite de productos.
     */
    public function validarLimiteProductos(int $tiendaId): void
    {
        $plan = $this->obtenerPlan($tiendaId);

        if ($plan->limite_productos === null) {
            return;
        }

        $count = Producto::count();

        if ($count >= $plan->limite_productos) {
            throw LimitePlanExcedidoException::productos($plan->limite_productos);
        }
    }

    /**
     * Valida que la tienda no haya excedido el límite de usuarios.
     */
    public function validarLimiteUsuarios(int $tiendaId): void
    {
        $plan = $this->obtenerPlan($tiendaId);

        if ($plan->limite_usuarios === null) {
            return;
        }

        $count = User::where('tienda_id', $tiendaId)->count();

        if ($count >= $plan->limite_usuarios) {
            throw LimitePlanExcedidoException::usuarios($plan->limite_usuarios);
        }
    }

    /**
     * Valida que la tienda no haya excedido el límite de almacenes.
     */
    public function validarLimiteAlmacenes(int $tiendaId): void
    {
        $plan = $this->obtenerPlan($tiendaId);

        if ($plan->limite_almacenes === null) {
            return;
        }

        $count = Almacen::count();

        if ($count >= $plan->limite_almacenes) {
            throw LimitePlanExcedidoException::almacenes($plan->limite_almacenes);
        }
    }

    /**
     * Valida que la tienda no haya excedido el límite de cajas.
     */
    public function validarLimiteCajas(int $tiendaId): void
    {
        $plan = $this->obtenerPlan($tiendaId);

        if ($plan->limite_cajas === null) {
            return;
        }

        $count = Caja::count();

        if ($count >= $plan->limite_cajas) {
            throw LimitePlanExcedidoException::cajas($plan->limite_cajas);
        }
    }

    /**
     * Obtiene el plan de la suscripción activa.
     */
    public function obtenerPlan(int $tiendaId): Plane
    {
        $suscripcion = $this->obtenerActiva($tiendaId);
        return $suscripcion->plan;
    }

    /**
     * Activa una suscripción (después del pago).
     */
    public function activarSuscripcion(int $tiendaId, int $planId, int $duracionMeses = 1): Suscripcion
    {
        return DB::transaction(function () use ($tiendaId, $planId, $duracionMeses) {
            // Cancelar suscripción anterior si existe
            Suscripcion::where('tienda_id', $tiendaId)
                ->whereIn('estado', ['trial', 'activa', 'vencida'])
                ->update(['estado' => 'cancelada']);

            $plan = Plane::findOrFail($planId);
            $inicio = now();
            $fin = $inicio->copy()->addMonths($duracionMeses);

            return Suscripcion::create([
                'tienda_id'     => $tiendaId,
                'plan_id'       => $planId,
                'estado'        => 'activa',
                'inicio_pago'   => $inicio,
                'fin_periodo'   => $fin->toDateString(),
                'proximo_cobro' => $fin->toDateString(),
                'auto_renovar'  => true,
            ]);
        });
    }

    /**
     * Devuelve info consolidada para el frontend.
     */
    public function estadoParaFrontend(int $tiendaId): array
    {
        $suscripcion = Suscripcion::where('tienda_id', $tiendaId)->latest()->first();

        if (!$suscripcion) {
            return ['estado' => 'sin_suscripcion'];
        }

        $plan = $suscripcion->plan;

        return [
            'estado'         => $suscripcion->estado,
            'plan'           => $plan->nombre,
            'plan_id'        => $plan->id,
            'precio'         => $plan->precio_mensual,
            'moneda'         => $plan->moneda,
            'es_trial'       => $suscripcion->estado === 'trial',
            'inicio_trial'   => $suscripcion->inicio_trial?->toISOString(),
            'fin_trial'      => $suscripcion->fin_trial?->toISOString(),
            'dias_restantes' => $suscripcion->fin_trial
                ? max(0, now()->diffInDays($suscripcion->fin_trial, false))
                : null,
            'fin_periodo'    => $suscripcion->fin_periodo?->toDateString(),
            'limites'        => [
                'productos' => $plan->limite_productos,
                'usuarios'  => $plan->limite_usuarios,
                'almacenes' => $plan->limite_almacenes,
                'cajas'     => $plan->limite_cajas,
            ],
        ];
    }
}
