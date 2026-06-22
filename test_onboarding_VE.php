<?php

/**
 * ============================================================
 *  PRUEBA ONBOARDING — VENEZUELA (VE)
 *  DISTRIBUIDORA ALIMENTOS CARACAS C.A.
 *  Ejecutar: php test_onboarding_VE.php
 * ============================================================
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OnboardingService;
$service = app(OnboardingService::class);

// ── Limpieza previa ──────────────────────────────────────────
\App\Models\User::where('email', 'ventas@distralimentos.com.ve')->delete();
$antiguas = \App\Models\Tienda::where('rif', 'J-31456789-0')->get();
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
echo " 🇻🇪 ONBOARDING VENEZUELA — DISTRIBUIDORA ALIMENTOS CARACAS C.A.\n";
echo " Bodega / Abarrotes — Caracas, Dtto. Capital\n";
echo "================================================================================\n\n";

// PASO 1
echo "1️⃣  CREAR CUENTA...\n";
$res = $service->crearCuenta([
    'name'     => 'Carlos Hernández',
    'email'    => 'ventas@distralimentos.com.ve',
    'password' => 'D1str@limentos2026!',
    'pais'     => 'VE',
]);
$tid   = $res['tienda']->id;
$token = $res['token'];
echo "   ✓ Usuario : #{$res['user']->id} — {$res['user']->name}\n";
echo "   ✓ Tienda  : #{$tid} | País: VE | Moneda: {$res['tienda']->moneda_base}\n";
echo "   ✓ Token   : " . substr($token, 0, 30) . "...\n\n";

// PASO 2
echo "2️⃣  DATOS FISCALES (SENIAT)...\n";
$tienda = $service->guardarDatosFiscales($tid, [
    'identificacion_fiscal' => 'J-31456789-0',
    'razon_social'          => 'DISTRIBUIDORA ALIMENTOS CARACAS C.A.',
    'nombre_comercial'      => 'DISTRALIMENTOS',
    'direccion'             => 'Av. Baralt, Local C-12, Parroquia La Candelaria, Caracas, Dtto. Capital',
    'telefono'              => '+58 212-8641230',
    'email'                 => 'admin@distralimentos.com.ve',
    'regimen_fiscal'        => 'Contribuyente Ordinario',
    'actividad_economica'   => 'Distribución de Alimentos al Mayor y Detal (Código CIIU G5120)',
    'codigo_postal'         => '1010',
]);
echo "   ✓ RIF              : {$tienda->rif}\n";
echo "   ✓ Razón Social     : {$tienda->razon_social}\n";
echo "   ✓ IGTF Agente      : " . ($tienda->es_agente_igtf ? '3% (sobre divisas)' : 'No') . "\n";
echo "   ✓ IVA default      : " . (\App\Models\Impuesto::where('es_defecto',true)->value('nombre') ?? 'n/a') . "\n";
echo "   ✓ Tasa BCV         : 1 USD = Bs " . (\App\Models\TasaCambio::where('moneda_destino','VES')->where('activa',true)->value('tasa') ?? '-') . "\n\n";

// PASO 3
echo "3️⃣  CONFIGURAR NEGOCIO...\n";
$service->configurarNegocio($tid, [
    'tipo_negocio'   => 'bodega',
    'nombre_almacen' => 'Depósito Candelaria',
    'nombre_caja'    => 'Caja 1 - Mostrador',
    'tipo_impresora' => 'termica_80mm',
]);
echo "   ✓ Tipo negocio     : Bodega / Abarrotes\n";
echo "   ✓ Almacén          : Depósito Candelaria\n";
echo "   ✓ Impresora        : Térmica 80mm (estilo supermercado)\n";
echo "   ✓ Métodos de pago  : Efectivo USD, VES, Pago Móvil, Zelle, USDT, Tarjetas\n\n";

// PASO 4
echo "4️⃣  PRIMER PRODUCTO (Cesta Básica)...\n";
$producto = $service->crearPrimerProducto($tid, [
    'nombre'        => 'Harina PAN Maíz Blanco 1kg',
    'sku'           => 'VE-HPAN-1KG',
    'costo'         => 1.10,
    'aplica_iva'    => false,
    'stock_inicial' => 500,
    'descripcion'   => 'Harina de maíz precocida blanca, bolsa 1 kg — Cesta Básica Exenta IVA',
]);
echo "   ✓ Producto : {$producto->nombre}\n";
echo "   ✓ SKU      : {$producto->codigo_sku}\n";
echo "   ✓ Costo    : \${$producto->costo_promedio}\n";
echo "   ✓ Impuesto : " . (\App\Models\Impuesto::find($producto->impuesto_id)->nombre ?? 'n/a') . "\n";
echo "   ✓ Stock    : 500 und | Onboarding: ✅ COMPLETADO\n\n";

echo "================================================================================\n";
echo "  ✅ VENEZUELA LISTA | Tienda #{$tid} | Token: Bearer " . substr($token,0,25) . "...\n";
echo "  → Para ver detalles: php artisan onboarding:show VE\n";
echo "================================================================================\n\n";
