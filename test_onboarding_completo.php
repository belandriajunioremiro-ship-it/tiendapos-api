<?php

/**
 * ============================================================
 *  PRUEBA COMPLETA DEL FLUJO DE ONBOARDING — TIENDAPOS API
 *  Ejecutar con: php test_onboarding_completo.php
 * ============================================================
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OnboardingService;

$service = app(OnboardingService::class);

// ── Limpieza para poder relanzar el script ──────────────────
\App\Models\User::where('email', 'test@onboarding.com')->delete();

// Limpiar tiendas de prueba anteriores
$tiendasAntiguas = \App\Models\Tienda::where('razon_social', 'Bodega La Principal C.A.')->get();
foreach ($tiendasAntiguas as $t) {
    \App\Models\TiendaOnboarding::where('tienda_id', $t->id)->delete();
    \App\Models\Suscripcion::where('tienda_id', $t->id)->delete();
    \App\Models\ConfiguracionImpresora::where('tienda_id', $t->id)->delete();
    $t->delete();
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║     PRUEBA INTEGRAL: ONBOARDING API — TIENDAPOS 2026    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// ──────────────────────────────────────────────────────────────
// PASO 1: Crear Cuenta (Usuario + Tienda + Trial)
// ──────────────────────────────────────────────────────────────
echo "════ PASO 1: CREAR CUENTA ══════════════════════════════════\n";
$res1 = $service->crearCuenta([
    'name'     => 'María González',
    'email'    => 'test@onboarding.com',
    'password' => '12345678',
    'pais'     => 'VE',
]);

$tiendaId = $res1['tienda']->id;
$token     = $res1['token'];

echo "  ✓ Usuario creado   : #{$res1['user']->id} — {$res1['user']->name}\n";
echo "  ✓ Tienda creada    : #{$res1['tienda']->id} — {$res1['tienda']->razon_social}\n";
echo "  ✓ País             : {$res1['tienda']->pais}\n";
echo "  ✓ Moneda base      : {$res1['tienda']->moneda_base}\n";
echo "  ✓ Token Sanctum    : " . substr($token, 0, 20) . "...\n\n";

// ──────────────────────────────────────────────────────────────
// PASO 2: Datos Fiscales + Siembra de Impuestos y Monedas
// ──────────────────────────────────────────────────────────────
echo "════ PASO 2: DATOS FISCALES ════════════════════════════════\n";
$tienda = $service->guardarDatosFiscales($tiendaId, [
    'identificacion_fiscal' => 'J-30123456-7',
    'razon_social'          => 'Bodega La Principal C.A.',
    'nombre_comercial'      => 'Bodega La Principal',
    'direccion'             => 'Calle Principal, Local 5, Barquisimeto, Lara',
    'telefono'              => '+58 251-5551234',
    'email'                 => 'bodega@test.com',
    'regimen_fiscal'        => 'Ordinario',
    'actividad_economica'   => 'Venta al Mayor y Detal de Alimentos',
]);

echo "  ✓ RIF actualizado  : {$tienda->rif}\n";
echo "  ✓ Razón Social     : {$tienda->razon_social}\n";
echo "  ✓ IGTF Agente      : " . ($tienda->es_agente_igtf ? '3%' : 'No aplica') . "\n";

$impuestos = \App\Models\Impuesto::all();
echo "  ✓ Impuestos sembrados:\n";
foreach ($impuestos as $imp) {
    $default = $imp->es_defecto ? ' ← DEFAULT' : '';
    echo "      [{$imp->tipo}] {$imp->nombre} ({$imp->porcentaje}%){$default}\n";
}

$monedas = \App\Models\TiendaMoneda::all();
echo "  ✓ Monedas habilitadas:\n";
foreach ($monedas as $m) {
    echo "      {$m->moneda} (ventas: " . ($m->acepta_ventas ? 'sí' : 'no') . ")\n";
}

$tasa = \App\Models\TasaCambio::where('moneda_destino', 'VES')->where('activa', true)->first();
if ($tasa) {
    echo "  ✓ Tasa BCV inicial : 1 USD = Bs {$tasa->tasa}\n";
}
echo "\n";

// ──────────────────────────────────────────────────────────────
// PASO 3: Configurar Negocio
// ──────────────────────────────────────────────────────────────
echo "════ PASO 3: CONFIGURAR NEGOCIO ════════════════════════════\n";
$service->configurarNegocio($tiendaId, [
    'tipo_negocio'   => 'bodega',
    'nombre_almacen' => 'Depósito Principal',
    'nombre_caja'    => 'Caja 1',
    'tipo_impresora' => 'termica_80mm',
]);

$categorias = \App\Models\CategoriaProducto::pluck('nombre')->toArray();
echo "  ✓ Categorías creadas (" . count($categorias) . "):\n";
foreach (array_slice($categorias, -6) as $cat) {
    echo "      - {$cat}\n";
}

$metodos = \App\Models\MetodoPago::where('activo', true)->get(['nombre', 'moneda', 'grava_igtf']);
echo "  ✓ Métodos de pago (" . $metodos->count() . "):\n";
foreach ($metodos as $mp) {
    $igtf = $mp->grava_igtf ? ' [IGTF]' : '';
    echo "      - {$mp->nombre} ({$mp->moneda}){$igtf}\n";
}

$cliente = \App\Models\Cliente::where('numero_documento', '00000000')->first();
echo "  ✓ Cliente default  : {$cliente->nombre}\n";

$margen = \App\Models\MargenGanancia::where('es_defecto', true)->first();
echo "  ✓ Margen default   : {$margen->nombre} ({$margen->porcentaje}%)\n";

$lista = \App\Models\ListaPrecio::where('nombre', 'Precio detal')->first();
echo "  ✓ Lista de precio  : {$lista->nombre}\n";

$plantilla = \App\Models\PlantillasImpresion::first();
echo "  ✓ Plantilla        : {$plantilla->nombre} ({$plantilla->tipo})\n\n";

// ──────────────────────────────────────────────────────────────
// PASO 4: Primer Producto + Inventario
// ──────────────────────────────────────────────────────────────
echo "════ PASO 4: PRIMER PRODUCTO ═══════════════════════════════\n";
$producto = $service->crearPrimerProducto($tiendaId, [
    'nombre'        => 'Harina PAN Maíz Blanco 1kg',
    'sku'           => 'HPAN-001-' . rand(100, 999),
    'costo'         => 1.20,
    'aplica_iva'    => false,   // Cesta básica → Exento
    'stock_inicial' => 100,
    'descripcion'   => 'Harina de maíz precocida blanca, bolsa 1 kg',
]);

$variante    = \App\Models\VarianteProducto::where('producto_id', $producto->id)->first();
$inventario  = \App\Models\Inventario::where('variante_id', $variante->id)->first();
$impuesto    = \App\Models\Impuesto::find($producto->impuesto_id);

echo "  ✓ Producto creado  : {$producto->nombre}\n";
echo "  ✓ SKU              : {$producto->codigo_sku}\n";
echo "  ✓ Costo            : \${$producto->costo_promedio}\n";
echo "  ✓ Impuesto         : {$impuesto->nombre}\n";
echo "  ✓ Stock inicial    : {$inventario->cantidad_disponible} unidades\n";

$onboarding = \App\Models\TiendaOnboarding::where('tienda_id', $tiendaId)->first();
echo "  ✓ Onboarding       : Paso {$onboarding->paso_actual} — " . ($onboarding->completado ? '✅ COMPLETADO' : 'pendiente') . "\n\n";

// ──────────────────────────────────────────────────────────────
// RESUMEN FINAL
// ──────────────────────────────────────────────────────────────
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║                   ✅ PRUEBA EXITOSA                     ║\n";
echo "╠══════════════════════════════════════════════════════════╣\n";
echo "║  Tienda ID     : #" . str_pad($tiendaId, 3) . "                                  ║\n";
echo "║  Token (guarda): " . substr($token, 0, 38) . "... ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";
echo "Para usar el token en Postman/frontend:\n";
echo "  Authorization: Bearer {$token}\n\n";
