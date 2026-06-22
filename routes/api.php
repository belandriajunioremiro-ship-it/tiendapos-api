<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TiendaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\VarianteController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\InventarioController;
use App\Http\Controllers\Api\AlmacenController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\OrdenCompraController;
use App\Http\Controllers\Api\RecepcionCompraController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ImpuestoController;
use App\Http\Controllers\Api\DescuentoController;
use App\Http\Controllers\Api\MetodoPagoController;
use App\Http\Controllers\Api\TasaCambioController;
use App\Http\Controllers\Api\ListaPrecioController;
use App\Http\Controllers\Api\MargenController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DevolucionController;
use App\Http\Controllers\Api\CreditoController;
use App\Http\Controllers\Api\AbonoController;
use App\Http\Controllers\Api\TrasladoStockController;
use App\Http\Controllers\Api\AjusteInventarioController;
use App\Http\Controllers\Api\SesionCajaController;
use App\Http\Controllers\Api\MovimientoCajaController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\SuscripcionController;
use App\Http\Controllers\Api\V1\PasswordResetController;

/*
|--------------------------------------------------------------------------
| RUTAS API TiendaPOS v2.1 — Prefijo unificado /api/v1/
|--------------------------------------------------------------------------
|
| Estructura de grupos:
|   PUBLICAS           (6 rutas)   — sin auth
|   AUTENTICADAS       (5 rutas)   — auth:sanctum + activo
|   ONBOARDING         (5 rutas)   — auth:sanctum + activo (sin suscripcion)
|   SUSCRIPCION        (4 rutas)   — auth:sanctum + activo (sin suscripcion)
|   NEGOCIO            (70+ rutas) — auth:sanctum + activo + suscripcion
|     ├─ POS             (20 rutas) — productos, clientes, ventas, caja
|     ├─ INVENTARIO      (15 rutas) — inventario, almacenes, traslados, ajustes, recepciones
|     ├─ COMPRAS         (7 rutas)  — proveedores, ordenes-compra
|     ├─ CREDITOS        (7 rutas)  — creditos, abonos, devoluciones
|     └─ CONFIGURACION   (21 rutas) — categorias, impuestos, descuentos, metodos-pago,
|                                      tasas-cambio, listas-precio, margenes, variantes,
|                                      sesiones-caja, movimientos-caja, dashboard
|   ADMIN              (9 rutas)   — auth:sanctum + activo + suscripcion + role:admin
|
| Middlewares registrados (bootstrap/app.php):
|   activo        → EnsureUserIsActive
|   suscripcion   → CheckSuscripcionActiva
|   plan.limits   → EnforcePlanLimits (parametrizable: productos, usuarios, almacenes, cajas)
|   role          → CheckRole (parametrizable: admin, supervisor, cajero)
|   permission    → CheckPermission (parametrizable: anular_venta, crear_devolucion, etc.)
|
| Rate limiters registrados (AppServiceProvider):
|   login          → 5/min por IP
|   password-reset → 3/min por IP (forgot-password)
|   reset-password → 5/min por IP
|   verify-token   → 10/min por IP
|   register       → 3/min por IP (onboarding/cuenta)
|
*/

Route::prefix('v1')->group(function () {

    // ═══════════════════════════════════════════════════════════════════════
    //  RUTAS PÚBLICAS (sin autenticación)
    // ═══════════════════════════════════════════════════════════════════════

    // ─── AUTH PÚBLICO ──────────────────────────────────────────────────
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::post('/auth/forgot-password', [PasswordResetController::class, 'enviarToken'])
        ->middleware('throttle:password-reset');

    Route::post('/auth/reset-password', [PasswordResetController::class, 'resetearPassword'])
        ->middleware('throttle:reset-password');

    Route::post('/auth/verify-token', [PasswordResetController::class, 'verificarToken'])
        ->middleware('throttle:verify-token');

    // ─── ONBOARDING PÚBLICO ─────────────────────────────────────────────
    Route::post('/onboarding/cuenta', [OnboardingController::class, 'crearCuenta'])
        ->middleware('throttle:register');

    Route::get('/onboarding/etiquetas/{pais}', [OnboardingController::class, 'etiquetasPais']);

    // ─── PLANES PÚBLICO (catálogo para landing page) ────────────────────
    Route::get('/suscripcion/planes', [SuscripcionController::class, 'planes']);

    // ═══════════════════════════════════════════════════════════════════════
    //  RUTAS AUTENTICADAS (auth:sanctum + activo)
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['auth:sanctum', 'activo'])->group(function () {

        // ─── AUTH PRIVADO ────────────────────────────────────────────────
        Route::post('/auth/logout',             [AuthController::class, 'logout']);
        Route::get('/auth/me',                  [AuthController::class, 'me']);
        Route::post('/auth/refresh',            [AuthController::class, 'refresh']);
        Route::post('/auth/cambiar-password',   [AuthController::class, 'cambiarPassword']);

        // ─── TIENDA (lectura) ────────────────────────────────────────────
        Route::get('/tienda', [TiendaController::class, 'show']);

        // ─── ONBOARDING PRIVADO (no requiere suscripción activa) ──────────
        Route::prefix('/onboarding')->group(function () {
            Route::get('/estado',                  [OnboardingController::class, 'estado']);
            Route::post('/datos-fiscales',         [OnboardingController::class, 'datosFiscales']);
            Route::post('/configurar-negocio',     [OnboardingController::class, 'configurarNegocio']);
            Route::post('/primer-producto',        [OnboardingController::class, 'primerProducto']);
            Route::post('/saltar-primer-producto', [OnboardingController::class, 'saltarPrimerProducto']);
        });

        // ─── SUSCRIPCIÓN (accesible con trial vencido para pagar) ─────────
        Route::prefix('/suscripcion')->group(function () {
            Route::get('/estado',   [SuscripcionController::class, 'estado']);
            Route::post('/activar', [SuscripcionController::class, 'activar']);
            Route::post('/cancelar', [SuscripcionController::class, 'cancelar']);
        });

        // ═══════════════════════════════════════════════════════════════════
        //  RUTAS DE NEGOCIO (requieren suscripción activa)
        // ═══════════════════════════════════════════════════════════════════
        Route::middleware('suscripcion')->group(function () {
            Route::middleware('throttle:100,1')->group(function () {

            // ─── DASHBOARD ─────────────────────────────────────────────────
            Route::get('/dashboard', [DashboardController::class, 'index']);

            // ─── POS: PRODUCTOS ─────────────────────────────────────────────
            Route::apiResource('productos', ProductoController::class)->except(['store']);
            Route::post('/productos', [ProductoController::class, 'store'])
                ->middleware('plan.limits:productos')
                ->middleware('throttle:30,1');

            // ─── POS: VARIANTES ─────────────────────────────────────────────
            Route::post('/variantes',            [VarianteController::class, 'store'])->middleware('throttle:30,1');
            Route::put('/variantes/{id}',        [VarianteController::class, 'update'])->middleware('throttle:60,1');
            Route::delete('/variantes/{id}',     [VarianteController::class, 'destroy'])->middleware('throttle:30,1');

            // ─── POS: CLIENTES ──────────────────────────────────────────────
            Route::apiResource('clientes', ClienteController::class)->middleware('throttle:60,1');

            // ─── POS: VENTAS ────────────────────────────────────────────────
            Route::get('/ventas',            [VentaController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/ventas',           [VentaController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/ventas/{id}',       [VentaController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/ventas/{id}',       [VentaController::class, 'update'])->middleware('throttle:30,1');
            Route::post('/ventas/{id}/anular', [VentaController::class, 'destroy'])
                ->middleware('permission:anular_venta')
                ->middleware('throttle:10,1');

            // ─── POS: CAJA ──────────────────────────────────────────────────
            Route::apiResource('cajas', CajaController::class)->except(['store']);
            Route::post('/cajas', [CajaController::class, 'store'])
                ->middleware('plan.limits:cajas');
            Route::post('/cajas/{caja}/abrir',  [CajaController::class, 'abrir']);
            Route::post('/cajas/{caja}/cerrar', [CajaController::class, 'cerrar']);

            // ─── POS: SESIONES DE CAJA ──────────────────────────────────────
            Route::get('/sesiones-caja',         [SesionCajaController::class, 'index']);
            Route::get('/sesiones-caja/{id}',    [SesionCajaController::class, 'show']);

            // ─── POS: MOVIMIENTOS DE CAJA ──────────────────────────────────
            Route::get('/movimientos-caja',      [MovimientoCajaController::class, 'index']);
            Route::post('/movimientos-caja',     [MovimientoCajaController::class, 'store']);

            // ─── INVENTARIO ────────────────────────────────────────────────
            Route::apiResource('inventario', InventarioController::class);
            Route::apiResource('almacenes', AlmacenController::class)->except(['store']);
            Route::post('/almacenes', [AlmacenController::class, 'store'])
                ->middleware('plan.limits:almacenes');

            // ─── INVENTARIO: TRASLADOS ──────────────────────────────────────
            Route::get('/traslados',                  [TrasladoStockController::class, 'index']);
            Route::post('/traslados',                 [TrasladoStockController::class, 'store']);
            Route::get('/traslados/{id}',             [TrasladoStockController::class, 'show']);
            Route::put('/traslados/{id}',             [TrasladoStockController::class, 'update']);
            Route::delete('/traslados/{id}',          [TrasladoStockController::class, 'destroy']);
            Route::post('/traslados/{id}/confirmar',  [TrasladoStockController::class, 'confirmar']);

            // ─── INVENTARIO: AJUSTES ────────────────────────────────────────
            Route::get('/ajustes',                    [AjusteInventarioController::class, 'index']);
            Route::post('/ajustes',                   [AjusteInventarioController::class, 'store']);
            Route::get('/ajustes/{id}',               [AjusteInventarioController::class, 'show']);
            Route::put('/ajustes/{id}',               [AjusteInventarioController::class, 'update']);
            Route::delete('/ajustes/{id}',            [AjusteInventarioController::class, 'destroy']);
            Route::post('/ajustes/{id}/confirmar',    [AjusteInventarioController::class, 'confirmar']);

            // ─── COMPRAS: PROVEEDORES ───────────────────────────────────────
            Route::apiResource('proveedores', ProveedorController::class);

            // ─── COMPRAS: ÓRDENES DE COMPRA ─────────────────────────────────
            Route::apiResource('ordenes-compra', OrdenCompraController::class);

            // ─── COMPRAS: RECEPCIONES ──────────────────────────────────────
            Route::get('/recepciones',         [RecepcionCompraController::class, 'index']);
            Route::post('/recepciones',        [RecepcionCompraController::class, 'store']);
            Route::get('/recepciones/{id}',    [RecepcionCompraController::class, 'show']);

            // ─── CRÉDITOS Y ABONOS ──────────────────────────────────────────
            Route::apiResource('creditos', CreditoController::class);
            Route::post('/creditos', [CreditoController::class, 'store'])
                ->middleware('permission:crear_credito');

            Route::get('/abonos',  [AbonoController::class, 'index']);
            Route::post('/abonos', [AbonoController::class, 'store'])
                ->middleware('permission:registrar_abono');

            // ─── DEVOLUCIONES ──────────────────────────────────────────────
            Route::get('/devoluciones',         [DevolucionController::class, 'index']);
            Route::post('/devoluciones',        [DevolucionController::class, 'store'])
                ->middleware('permission:crear_devolucion');
            Route::get('/devoluciones/{id}',   [DevolucionController::class, 'show']);

            // ─── CONFIGURACIÓN: CATEGORÍAS ──────────────────────────────────
            Route::apiResource('categorias', CategoriaController::class);

            // ─── CONFIGURACIÓN: IMPUESTOS ───────────────────────────────────
            Route::apiResource('impuestos', ImpuestoController::class);

            // ─── CONFIGURACIÓN: DESCUENTOS ──────────────────────────────────
            Route::apiResource('descuentos', DescuentoController::class);

            // ─── CONFIGURACIÓN: MÉTODOS DE PAGO ────────────────────────────
            Route::apiResource('metodos-pago', MetodoPagoController::class);

            // ─── CONFIGURACIÓN: TASAS DE CAMBIO ─────────────────────────────
            Route::apiResource('tasas-cambio', TasaCambioController::class);

            // ─── CONFIGURACIÓN: LISTAS DE PRECIO ────────────────────────────
            Route::apiResource('listas-precio', ListaPrecioController::class);

            // ─── CONFIGURACIÓN: MÁRGENES DE GANANCIA ────────────────────────
            Route::apiResource('margenes', MargenController::class);

            // ═════════════════════════════════════════════════════════════════
            //  RUTAS SOLO ADMIN (role:admin)
            // ═════════════════════════════════════════════════════════════════
            Route::middleware('role:admin')->group(function () {

                // ─── ADMIN: USUARIOS ─────────────────────────────────────────
                Route::apiResource('usuarios', UsuarioController::class)->except(['store']);
                Route::post('/usuarios', [UsuarioController::class, 'store'])
                    ->middleware('plan.limits:usuarios');

                // ─── ADMIN: REPORTES ──────────────────────────────────────────
                Route::prefix('/reportes')->group(function () {
                    Route::get('/ventas',       [ReporteController::class, 'ventas']);
                    Route::get('/inventario',   [ReporteController::class, 'inventario']);
                    Route::get('/rentabilidad', [ReporteController::class, 'rentabilidad']);
                    Route::get('/creditos',     [ReporteController::class, 'creditos']);
                });

                // ─── ADMIN: TIENDA (escritura) ───────────────────────────────
                Route::put('/tienda', [TiendaController::class, 'update']);
            }); // ← cierra role:admin
            }); // ← cierra throttle:100,1
        }); // ← cierra suscripcion
    }); // ← cierra auth:sanctum + activo
}); // ← cierra prefix('v1')
