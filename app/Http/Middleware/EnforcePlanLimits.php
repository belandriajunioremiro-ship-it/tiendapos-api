<?php

namespace App\Http\Middleware;

use App\Exceptions\Suscripcion\LimitePlanExcedidoException;
use App\Services\SuscripcionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlanLimits
{
    public function __construct(private SuscripcionService $service) {}

    /**
     * Middleware parametrizable. Uso en rutas:
     *   ->middleware('plan.limits:productos')
     *   ->middleware('plan.limits:usuarios')
     *   ->middleware('plan.limits:almacenes')
     *   ->middleware('plan.limits:cajas')
     */
    public function handle(Request $request, Closure $next, string $recurso): Response
    {
        $user = $request->user();

        if (!$user || !$user->tienda_id) {
            return $next($request);
        }

        try {
            match ($recurso) {
                'productos' => $this->service->validarLimiteProductos($user->tienda_id),
                'usuarios'  => $this->service->validarLimiteUsuarios($user->tienda_id),
                'almacenes' => $this->service->validarLimiteAlmacenes($user->tienda_id),
                'cajas'     => $this->service->validarLimiteCajas($user->tienda_id),
                default     => null,
            };
        } catch (LimitePlanExcedidoException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'limite_plan_excedido',
                'recurso' => $recurso,
                'message' => $e->getMessage(),
                'action'  => 'actualizar_plan',
            ], 402);
        }

        return $next($request);
    }
}
