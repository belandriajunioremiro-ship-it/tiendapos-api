<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'activo'       => \App\Http\Middleware\EnsureUserIsActive::class,
            'suscripcion'  => \App\Http\Middleware\CheckSuscripcionActiva::class,
            'plan.limits'  => \App\Http\Middleware\EnforcePlanLimits::class,
            'role'          => \App\Http\Middleware\CheckRole::class,
            'permission'    => \App\Http\Middleware\CheckPermission::class,
        ]);

        $middleware->append(\App\Http\Middleware\AddSecurityHeaders::class);

        $middleware->throttleApi('api');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\Suscripcion\SuscripcionVencidaException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'suscripcion_vencida',
                'message' => $e->getMessage(),
                'action'  => 'actualizar_plan',
            ], 402);
        });

        $exceptions->render(function (\App\Exceptions\Suscripcion\LimitePlanExcedidoException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'limite_plan_excedido',
                'message' => $e->getMessage(),
                'action'  => 'actualizar_plan',
            ], 402);
        });

    })->create();
