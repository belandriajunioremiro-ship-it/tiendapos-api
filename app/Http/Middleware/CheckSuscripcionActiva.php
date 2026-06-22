<?php

namespace App\Http\Middleware;

use App\Exceptions\Suscripcion\SuscripcionVencidaException;
use App\Services\SuscripcionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSuscripcionActiva
{
    public function __construct(private SuscripcionService $service) {}

    /**
     * Bloquea el acceso si la suscripción de la tienda está vencida o suspendida.
     * Permite acceso a rutas de onboarding y suscripciones para que el usuario
     * pueda pagar y reactivar.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->tienda_id) {
            return $next($request);
        }

        try {
            $this->service->verificarAcceso($user->tienda_id);
        } catch (SuscripcionVencidaException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'suscripcion_vencida',
                'message' => $e->getMessage(),
                'action'  => 'actualizar_plan',
                'redirect'=> '/billing',
            ], 402); // 402 Payment Required
        }

        return $next($request);
    }
}
