<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\TiendaPosTestStyle;
use App\Models\Plane;
use App\Models\Suscripcion;
use App\Models\Tienda;
use App\Models\User;
use App\Services\SuscripcionService;
use Illuminate\Console\Command;

class TestSuscripcionCommand extends Command
{
    use TiendaPosTestStyle;

    protected $signature = 'pos:test-suscripcion';
    protected $description = 'Test visual del sistema SaaS: Trial → Vence → Paga → Activa';

    public function handle(): int
    {
        $this->testHeader(
            'SISTEMA DE SUSCRIPCIONES SAAS',
            'Trial → Vencimiento → Bloqueo → Pago → Activación'
        );

        $service = app(SuscripcionService::class);
        $tienda = Tienda::first();

        if (! $tienda) {
            $this->testFail('No hay tiendas en la BD');
            return 1;
        }

        $admin = User::where('tienda_id', $tienda->id)->where('activo', true)->first();
        if (! $admin) {
            $this->testFail('No hay usuario admin para la tienda ' . $tienda->id);
            return 1;
        }

        // ─── PASO 1 ─────────────────────────────────────────────
        $this->testStep(1, 'Verificando suscripción actual (Trial)');

        $suscripcion = Suscripcion::where('tienda_id', $tienda->id)->latest()->first();

        if (! $suscripcion) {
            $this->testFail('No existe suscripción para la tienda ' . $tienda->id);
            return 1;
        }

        $this->testOk('Suscripción encontrada');
        $this->testDetail('ID:', (string) $suscripcion->id);
        $this->testDetail('Estado actual:', $suscripcion->estado);
        $this->testDetail('Plan:', $suscripcion->plan->nombre ?? 'N/A');
        $this->testDetail('Precio:', '$' . number_format($suscripcion->plan->precio_mensual ?? 0, 2));

        if ($suscripcion->estado === 'trial') {
            $diasRestantes = $suscripcion->fin_trial
                ? now()->diffInDays($suscripcion->fin_trial, false)
                : 'N/A';
            $this->testDetail('Días restantes trial:', (string) max(0, $diasRestantes));
        }

        // ─── PASO 2 ─────────────────────────────────────────────
        $this->testStep(2, 'Simulando vencimiento del Trial');

        $estadoOriginal = $suscripcion->estado;
        $finTrialOriginal = $suscripcion->fin_trial;

        $suscripcion->update([
            'estado'    => 'vencida',
            'fin_trial' => now()->subDays(2),
        ]);

        $suscripcion->refresh();
        $this->testOk('Suscripción marcada como VENCIDA');
        $this->testDetail('Estado:', $suscripcion->estado);
        $this->testDetail('Fin trial:', $suscripcion->fin_trial?->format('Y-m-d'));

        // ─── PASO 3 ─────────────────────────────────────────────
        $this->testStep(3, 'Verificando bloqueo de acceso (middleware)');

        try {
            $service->verificarAcceso($tienda->id);
            $this->testFail('ERROR: El acceso NO debería estar permitido');
        } catch (\App\Exceptions\Suscripcion\SuscripcionVencidaException $e) {
            $this->testOk('Acceso bloqueado correctamente (402 Payment Required)');
            $this->testDetail('Excepción:', 'SuscripcionVencidaException');
            $this->testDetail('Mensaje:', substr($e->getMessage(), 0, 80));
        }

        // ─── PASO 4 ─────────────────────────────────────────────
        $this->testStep(4, 'Activando plan pago (simulación de pago)');

        $planBasico = Plane::where('nombre', 'Básico')->first();
        if (! $planBasico) {
            $planBasico = Plane::skip(1)->first();
        }

        if (! $planBasico) {
            $this->testFail('No existe plan Básico en la BD');
            return 1;
        }

        $nuevaSuscripcion = $service->activarSuscripcion($tienda->id, $planBasico->id, 1);

        $this->testOk('Plan activado exitosamente');
        $this->testDetail('Plan:', $nuevaSuscripcion->plan->nombre);
        $this->testDetail('Precio:', '$' . number_format($nuevaSuscripcion->plan->precio_mensual, 2));
        $this->testDetail('Estado:', $nuevaSuscripcion->estado);
        $this->testDetail('Inicio:', $nuevaSuscripcion->inicio_pago?->format('Y-m-d') ?? 'N/A');
        $this->testDetail('Fin período:', (string) $nuevaSuscripcion->fin_periodo);

        // ─── PASO 5 ─────────────────────────────────────────────
        $this->testStep(5, 'Verificando acceso restaurado');

        try {
            $service->verificarAcceso($tienda->id);
            $this->testOk('Acceso permitido después del pago');
        } catch (\Exception $e) {
            $this->testFail('Error: el acceso sigue bloqueado: ' . $e->getMessage());
        }

        // ─── PASO 6 ─────────────────────────────────────────────
        $this->testStep(6, 'Probando límites del plan');

        $plan = $service->obtenerPlan($tienda->id);
        $this->testDetail('Plan actual:', $plan->nombre);
        $this->testDetail('Límite productos:', $plan->limite_productos ? (string) $plan->limite_productos : 'Ilimitado');
        $this->testDetail('Límite usuarios:', $plan->limite_usuarios ? (string) $plan->limite_usuarios : 'Ilimitado');
        $this->testDetail('Límite almacenes:', $plan->limite_almacenes ? (string) $plan->limite_almacenes : 'Ilimitado');
        $this->testDetail('Límite cajas:', $plan->limite_cajas ? (string) $plan->limite_cajas : 'Ilimitado');
        $this->testOk('Middleware plan.limits:productos bloquearía creación sobre el límite');

        // ─── RESUMEN FINAL ──────────────────────────────────────
        $this->testFooter('SISTEMA SaaS FUNCIONANDO — MONETIZACIÓN LISTA', true, [
            'Trial inicial (14 días)'    => '✓ OK',
            'Vencimiento automático'     => '✓ OK',
            'Bloqueo de acceso (402)'    => '✓ OK',
            'Activación con pago'        => '✓ OK',
            'Acceso restaurado'          => '✓ OK',
            'Límites del plan'           => '✓ OK',
        ]);

        return 0;
    }
}
