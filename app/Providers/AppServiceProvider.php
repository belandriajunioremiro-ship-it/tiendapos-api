<?php

namespace App\Providers;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\CuentaCredito;
use App\Models\DevolucionVenta;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Observers\AuditoriaObserver;
use App\Policies\CajaPolicy;
use App\Policies\ClientePolicy;
use App\Policies\CuentaCreditoPolicy;
use App\Policies\DevolucionPolicy;
use App\Policies\InventarioPolicy;
use App\Policies\ProductoPolicy;
use App\Policies\UsuarioPolicy;
use App\Policies\VentaPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Producto::class       => ProductoPolicy::class,
        Venta::class         => VentaPolicy::class,
        Cliente::class       => ClientePolicy::class,
        Caja::class          => CajaPolicy::class,
        CuentaCredito::class => CuentaCreditoPolicy::class,
        DevolucionVenta::class => DevolucionPolicy::class,
        Inventario::class    => InventarioPolicy::class,
        User::class          => UsuarioPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('reset-password', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('verify-token', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        $modelosAuditables = [
            Producto::class,
            Venta::class,
            Cliente::class,
            Caja::class,
            CuentaCredito::class,
            DevolucionVenta::class,
            Inventario::class,
        ];

        foreach ($modelosAuditables as $modelo) {
            $modelo::observe(AuditoriaObserver::class);
        }
    }
}
