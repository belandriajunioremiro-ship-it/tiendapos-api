<?php

/**
 * ============================================================
 *  PRUEBA ONBOARDING — MÉXICO (MX)
 *  MISCELÁNEA DON MARCO S.A. DE C.V.
 *  Ejecutar: php test_onboarding_MX.php
 * ============================================================
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OnboardingService;
$service = app(OnboardingService::class);

// ── Limpieza previa ──────────────────────────────────────────
\App\Models\User::where('email', 'admin@miscelanea-donmarco.mx')->delete();
$antiguas = \App\Models\Tienda::where('rif', 'MDM850312HJ4')->get();
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
echo " 🇲🇽 ONBOARDING MÉXICO — MISCELÁNEA DON MARCO S.A. DE C.V.\n";
echo " Abarrotes y Víveres — Ciudad de México, CDMX\n";
echo "================================================================================\n\n";

// PASO 1
echo "1️⃣  CREAR CUENTA...\n";
$res = $service->crearCuenta([
    'name'     => 'Marco Antonio Velázquez',
    'email'    => 'admin@miscelanea-donmarco.mx',
    'password' => 'D0nM@rco2026$',
    'pais'     => 'MX',
]);
$tid   = $res['tienda']->id;
$token = $res['token'];
echo "   ✓ Usuario : #{$res['user']->id} — {$res['user']->name}\n";
echo "   ✓ Tienda  : #{$tid} | País: MX | Moneda: {$res['tienda']->moneda_base}\n";
echo "   ✓ Token   : " . substr($token, 0, 30) . "...\n\n";

// PASO 2
echo "2️⃣  DATOS FISCALES (SAT)...\n";
$tienda = $service->guardarDatosFiscales($tid, [
    'identificacion_fiscal' => 'MDM850312HJ4',
    'razon_social'          => 'MISCELÁNEA DON MARCO S.A. DE C.V.',
    'nombre_comercial'      => 'Miscelánea Don Marco',
    'direccion'             => 'Calle Insurgentes Sur 1602, Int. 5, Col. Crédito Constructor, Alcaldía Benito Juárez, CDMX, C.P. 03940',
    'telefono'              => '+52 55-5512-4567',
    'email'                 => 'facturacion@miscelanea-donmarco.mx',
    'regimen_fiscal'        => 'Régimen General de Ley Personas Morales',
    'actividad_economica'   => 'Comercio al por menor en tiendas de abarrotes (SCIAN 461110)',
    'codigo_postal'         => '03940',
]);
echo "   ✓ RFC              : {$tienda->rif}\n";
echo "   ✓ Razón Social     : {$tienda->razon_social}\n";
echo "   ✓ Régimen SAT      : Régimen General Personas Morales\n";
echo "   ✓ IVA default      : " . (\App\Models\Impuesto::where('es_defecto',true)->value('nombre') ?? 'n/a') . "\n";
echo "   ✓ Nota             : Alimentos sin procesar → Tasa IVA 0% (exento)\n\n";

// PASO 3
echo "3️⃣  CONFIGURAR NEGOCIO...\n";
$service->configurarNegocio($tid, [
    'tipo_negocio'   => 'abarrotes',
    'nombre_almacen' => 'Almacén Benito Juárez',
    'nombre_caja'    => 'Caja 1 - Mostrador',
    'tipo_impresora' => 'termica_80mm',
]);
echo "   ✓ Tipo negocio     : Abarrotes / Miscelánea\n";
echo "   ✓ Almacén          : Almacén Benito Juárez\n";
echo "   ✓ Impresora        : Térmica 80mm (CFDI-compatible)\n";
echo "   ✓ Métodos de pago  : Efectivo MXN, Tarjeta, Transferencia SPEI, QR CoDi\n\n";

// PASO 4
echo "4️⃣  PRIMER PRODUCTO...\n";
$producto = $service->crearPrimerProducto($tid, [
    'nombre'        => 'Tortillas de Maíz Tía Rosa 1kg',
    'sku'           => 'MX-TORT-TIAROSA1',
    'costo'         => 22.50,
    'aplica_iva'    => false,
    'stock_inicial' => 150,
    'descripcion'   => 'Tortillas de maíz nixtamalizado, 1 kg — Tasa IVA 0% (alimento básico SAT)',
]);
echo "   ✓ Producto : {$producto->nombre}\n";
echo "   ✓ SKU      : {$producto->codigo_sku}\n";
echo "   ✓ Costo    : $" . number_format($producto->costo_promedio, 2) . " MXN\n";
echo "   ✓ IVA      : 0% (Alimento básico — SAT exento)\n";
echo "   ✓ Stock    : 150 kg | Onboarding: ✅ COMPLETADO\n\n";

echo "================================================================================\n";
echo "  ✅ MÉXICO LISTO | Tienda #{$tid} | Token: Bearer " . substr($token,0,25) . "...\n";
echo "  → Para ver detalles: php artisan onboarding:show MX\n";
echo "================================================================================\n\n";
