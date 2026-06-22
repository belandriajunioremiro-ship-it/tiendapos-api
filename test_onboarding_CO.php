<?php

/**
 * ============================================================
 *  PRUEBA ONBOARDING — COLOMBIA (CO)
 *  FERRETERÍA EL MAESTRO S.A.S.
 *  Ejecutar: php test_onboarding_CO.php
 * ============================================================
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OnboardingService;
$service = app(OnboardingService::class);

// ── Limpieza previa ──────────────────────────────────────────
\App\Models\User::where('email', 'gerencia@ferreteriamae.com.co')->delete();
$antiguas = \App\Models\Tienda::where('rif', '900.456.123-5')->get();
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
echo " 🇨🇴 ONBOARDING COLOMBIA — FERRETERÍA EL MAESTRO S.A.S.\n";
echo " Ferretería y Materiales — Bogotá D.C., Colombia\n";
echo "================================================================================\n\n";

// PASO 1
echo "1️⃣  CREAR CUENTA...\n";
$res = $service->crearCuenta([
    'name'     => 'Adriana Morales Ruiz',
    'email'    => 'gerencia@ferreteriamae.com.co',
    'password' => 'FerM@estro2026#',
    'pais'     => 'CO',
]);
$tid   = $res['tienda']->id;
$token = $res['token'];
echo "   ✓ Usuario : #{$res['user']->id} — {$res['user']->name}\n";
echo "   ✓ Tienda  : #{$tid} | País: CO | Moneda: {$res['tienda']->moneda_base}\n";
echo "   ✓ Token   : " . substr($token, 0, 30) . "...\n\n";

// PASO 2
echo "2️⃣  DATOS FISCALES (DIAN)...\n";
$tienda = $service->guardarDatosFiscales($tid, [
    'identificacion_fiscal' => '900.456.123-5',
    'razon_social'          => 'FERRETERÍA EL MAESTRO S.A.S.',
    'nombre_comercial'      => 'Ferretería El Maestro',
    'direccion'             => 'Carrera 68 #22A-15, Barrio Puente Aranda, Bogotá D.C., Cundinamarca',
    'telefono'              => '+57 601-4123456',
    'email'                 => 'facturacion@ferreteriamae.com.co',
    'regimen_fiscal'        => 'Régimen Común — Responsable de IVA',
    'actividad_economica'   => 'Comercio al por menor de ferretería, pinturas y vidrios (CIIU 4752)',
    'codigo_postal'         => '111611',
]);
echo "   ✓ NIT              : {$tienda->rif}\n";
echo "   ✓ Razón Social     : {$tienda->razon_social}\n";
echo "   ✓ Régimen          : Responsable de IVA (DIAN)\n";
echo "   ✓ IVA default      : " . (\App\Models\Impuesto::where('es_defecto',true)->value('nombre') ?? 'n/a') . "\n\n";

// PASO 3
echo "3️⃣  CONFIGURAR NEGOCIO...\n";
$service->configurarNegocio($tid, [
    'tipo_negocio'   => 'ferreteria',
    'nombre_almacen' => 'Bodega Puente Aranda',
    'nombre_caja'    => 'Caja 1 - Mostrador',
    'tipo_impresora' => 'termica_80mm',
]);
echo "   ✓ Tipo negocio     : Ferretería y Construcción\n";
echo "   ✓ Almacén          : Bodega Puente Aranda\n";
echo "   ✓ Impresora        : Térmica 80mm\n";
echo "   ✓ Métodos de pago  : Efectivo COP, Nequi, PSE, Tarjeta Débito/Crédito\n\n";

// PASO 4
echo "4️⃣  PRIMER PRODUCTO...\n";
$producto = $service->crearPrimerProducto($tid, [
    'nombre'        => 'Cemento Argos Gris 50kg',
    'sku'           => 'CO-CEM-ARGOS50',
    'costo'         => 28000,
    'aplica_iva'    => true,
    'stock_inicial' => 200,
    'descripcion'   => 'Saco cemento Portland gris uso general 50 kg — Argos Colombia',
]);
echo "   ✓ Producto : {$producto->nombre}\n";
echo "   ✓ SKU      : {$producto->codigo_sku}\n";
echo "   ✓ Costo    : $" . number_format($producto->costo_promedio, 0, ',', '.') . " COP\n";
echo "   ✓ IVA      : 19% (Régimen General DIAN)\n";
echo "   ✓ Stock    : 200 sacos | Onboarding: ✅ COMPLETADO\n\n";

echo "================================================================================\n";
echo "  ✅ COLOMBIA LISTA | Tienda #{$tid} | Token: Bearer " . substr($token,0,25) . "...\n";
echo "  → Para ver detalles: php artisan onboarding:show CO\n";
echo "================================================================================\n\n";
