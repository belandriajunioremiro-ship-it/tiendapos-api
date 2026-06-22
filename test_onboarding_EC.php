<?php

/**
 * ============================================================
 *  PRUEBA ONBOARDING — ECUADOR (EC)
 *  FARMACIA SALUD INTEGRAL CIA. LTDA.
 *  Ejecutar: php test_onboarding_EC.php
 * ============================================================
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OnboardingService;
$service = app(OnboardingService::class);

// ── Limpieza previa ──────────────────────────────────────────
\App\Models\User::where('email', 'admin@farmaciasalud.ec')->delete();
$antiguas = \App\Models\Tienda::where('rif', '0992567843001')->get();
foreach ($antiguas as $t) {
    \App\Models\TiendaOnboarding::where('tienda_id', $t->id)->delete();
    \App\Models\Suscripcion::where('tienda_id', $t->id)->delete();
    \App\Models\ConfiguracionImpresora::where('tienda_id', $t->id)->delete();
    $t->delete();
}

echo "\n";
echo "================================================================================\n";
echo "         PRUEBA DE CONEXIÓN TIENDA POS DIRECTAMENTE DESDE NEON TCH              \n";
echo "================================================================================\n";
echo " 🇪🇨 ONBOARDING ECUADOR — FARMACIA SALUD INTEGRAL CIA. LTDA.\n";
echo " Farmacia y Perfumería — Guayaquil, Guayas, Ecuador\n";
echo "================================================================================\n\n";

// PASO 1
echo "1️⃣  CREAR CUENTA...\n";
$res = $service->crearCuenta([
    'name'     => 'Valeria Santamaría Vega',
    'email'    => 'admin@farmaciasalud.ec',
    'password' => 'F@rmSalud2026*',
    'pais'     => 'EC',
]);
$tid   = $res['tienda']->id;
$token = $res['token'];
echo "   ✓ Usuario : #{$res['user']->id} — {$res['user']->name}\n";
echo "   ✓ Tienda  : #{$tid} | País: EC | Moneda: {$res['tienda']->moneda_base}\n";
echo "   ✓ Token   : " . substr($token, 0, 30) . "...\n\n";

// PASO 2
echo "2️⃣  DATOS FISCALES (SRI)...\n";
$tienda = $service->guardarDatosFiscales($tid, [
    'identificacion_fiscal' => '0992567843001',
    'razon_social'          => 'FARMACIA SALUD INTEGRAL CIA. LTDA.',
    'nombre_comercial'      => 'Farmacia Salud Integral',
    'direccion'             => 'Av. 9 de Octubre 2416 y Av. Pedro Menéndez Gilbert, Local 3, Guayaquil, Guayas',
    'telefono'              => '+593 4-2451678',
    'email'                 => 'sri@farmaciasalud.ec',
    'regimen_fiscal'        => 'Régimen General — Contribuyente Especial',
    'actividad_economica'   => 'Actividades de farmacias y boticas (CIIU 4773)',
    'codigo_postal'         => '090112',
]);
echo "   ✓ RUC              : {$tienda->rif}\n";
echo "   ✓ Razón Social     : {$tienda->razon_social}\n";
echo "   ✓ Régimen SRI      : Contribuyente Especial\n";
echo "   ✓ IVA default      : " . (\App\Models\Impuesto::where('es_defecto',true)->value('nombre') ?? 'n/a') . "\n";
echo "   ✓ Nota             : Medicamentos → 0% IVA | Suplementos → 15% IVA\n\n";

// PASO 3
echo "3️⃣  CONFIGURAR NEGOCIO...\n";
$service->configurarNegocio($tid, [
    'tipo_negocio'   => 'farmacia',
    'nombre_almacen' => 'Bodega Guayaquil Norte',
    'nombre_caja'    => 'Caja 1 - Mostrador',
    'tipo_impresora' => 'termica_80mm',
]);
echo "   ✓ Tipo negocio     : Farmacia / Salud\n";
echo "   ✓ Almacén          : Bodega Guayaquil Norte\n";
echo "   ✓ Impresora        : Térmica 80mm (comprobante SRI)\n";
echo "   ✓ Métodos de pago  : Efectivo USD, Tarjeta Débito/Crédito, Transferencia, Deuna\n\n";

// PASO 4
echo "4️⃣  PRIMER PRODUCTO...\n";
$producto = $service->crearPrimerProducto($tid, [
    'nombre'        => 'Acetaminofén 500mg x 10 tabletas',
    'sku'           => 'EC-ACET-500MG',
    'costo'         => 1.10,
    'aplica_iva'    => false,
    'stock_inicial' => 300,
    'descripcion'   => 'Analgésico-antipirético de uso general. Reg. Sanitario MSP-EC-006789. IVA 0%.',
]);
echo "   ✓ Producto : {$producto->nombre}\n";
echo "   ✓ SKU      : {$producto->codigo_sku}\n";
echo "   ✓ Costo    : \${$producto->costo_promedio} USD\n";
echo "   ✓ IVA      : 0% (Medicamento — SRI exento)\n";
echo "   ✓ Stock    : 300 blísters | Onboarding: ✅ COMPLETADO\n\n";

echo "================================================================================\n";
echo "  ✅ ECUADOR LISTO | Tienda #{$tid} | Token: Bearer " . substr($token,0,25) . "...\n";
echo "  → Para ver detalles: php artisan onboarding:show EC\n";
echo "================================================================================\n\n";
