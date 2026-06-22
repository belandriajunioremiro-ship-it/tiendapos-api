<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Suscripcion;

echo "=== PRUEBA DE SISTEMA DE SUSCRIPCIONES ===\n\n";

// 1. Obtener la suscripción más reciente
$s = Suscripcion::latest()->first();

if (!$s) {
    echo "❌ No hay suscripciones en la base de datos.\n";
    exit(1);
}

echo "Suscripción actual:\n";
echo "  ID: {$s->id}\n";
echo "  Tienda ID: {$s->tienda_id}\n";
echo "  Estado: {$s->estado}\n";
echo "  Fin Trial: {$s->fin_trial}\n\n";

// 2. Simular trial vencido (fecha en el pasado)
echo "⏳ Simulando trial vencido...\n";
$s->update(['fin_trial' => now()->subDays(1)]);
$s->refresh();

echo "  Nuevo fin_trial: {$s->fin_trial}\n\n";

// 3. Ejecutar el comando de verificación
echo "🔧 Ejecutando verificación de trials vencidos...\n";
$service = app(\App\Services\SuscripcionService::class);
$cantidad = $service->marcarTrialsVencidos();
echo "  ✓ Se marcaron {$cantidad} suscripción(es) como vencidas.\n\n";

// 4. Verificar estado actualizado
$s->refresh();
echo "Estado después de verificación:\n";
echo "  Estado: {$s->estado}\n\n";

// 5. Probar activación de plan
echo "💳 Activando plan Básico (ID: 2)...\n";
$nueva = $service->activarSuscripcion($s->tienda_id, 2, 1);
echo "  ✓ Nueva suscripción creada:\n";
echo "    ID: {$nueva->id}\n";
echo "    Estado: {$nueva->estado}\n";
echo "    Fin período: {$nueva->fin_periodo}\n\n";

// 6. Estado para frontend
echo "📊 Estado para frontend:\n";
$estado = $service->estadoParaFrontend($s->tienda_id);
print_r($estado);

echo "\n✅ PRUEBA COMPLETADA EXITOSAMENTE.\n";
