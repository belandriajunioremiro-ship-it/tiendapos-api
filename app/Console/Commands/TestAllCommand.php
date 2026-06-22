<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\TiendaPosTestStyle;
use Illuminate\Console\Command;

class TestAllCommand extends Command
{
    use TiendaPosTestStyle;

    protected $signature = 'pos:test-all
                            {--no-pause : Correr sin pausas entre tests (útil para CI)}';
    protected $description = 'Ejecuta todos los tests visuales del sistema en secuencia';

    public function handle(): int
    {
        $this->testHeader(
            'SUITE COMPLETA DE TESTS — TIENDAPOS',
            'Ejecutando los 5 tests principales en secuencia...'
        );

        $tests = [
            ['pos:test-auth',          'Sistema de Autenticación',        []],
            ['pos:test-multitenancy',   'Aislamiento Multi-Tienda',       []],
            ['pos:test-suscripcion',    'Suscripciones SaaS',             []],
            ['pos:test-onboarding',     'Onboarding Venezuela',           ['pais' => 'VE']],
            ['pos:test-pos',            'Venta POS Completa',             ['pais' => 'VE']],
        ];

        $resultados = [];

        foreach ($tests as $i => [$comando, $nombre, $params]) {
            $this->line("");
            $this->line("<fg=gray>" . str_repeat('─', 60) . "</>");
            $this->line("<fg=bright-magenta;options=bold>▶ EJECUTANDO TEST " . ($i + 1) . "/" . count($tests) . ": {$nombre}</>");
            $this->line("<fg=gray>" . str_repeat('─', 60) . "</>");
            $this->line("");

            $exitCode = $this->call($comando, $params);

            $resultados[] = [
                'test'      => $nombre,
                'comando'   => $comando,
                'exitCode'  => $exitCode,
                'resultado' => $exitCode === 0 ? '<fg=green>✓ PASÓ</>' : '<fg=red>✗ FALLÓ</>',
            ];

            if ($i < count($tests) - 1 && ! $this->option('no-pause')) {
                $this->line("");
                $this->testInfo('Presiona ENTER para continuar con el siguiente test...');
                $this->ask('Continuar?');
            }
        }

        // ─── RESUMEN FINAL ──────────────────────────────────────
        $this->testHeader(
            'RESUMEN FINAL — SUITE COMPLETA',
            'Resultados de los ' . count($tests) . ' tests ejecutados'
        );

        $rows = array_map(fn($r) => [$r['test'], $r['comando'], $r['resultado']], $resultados);
        $this->table(['Test', 'Comando', 'Estado'], $rows);

        $pasaron = count(array_filter($resultados, fn($r) => $r['exitCode'] === 0));
        $total = count($resultados);

        $this->testFooter(
            $pasaron === $total ? "TODOS LOS TESTS PASARON ({$pasaron}/{$total})" : "{$pasaron}/{$total} TESTS PASARON",
            $pasaron === $total,
            [
                'Total tests ejecutados' => (string) $total,
                'Tests exitosos'         => (string) $pasaron,
                'Tests fallidos'         => (string) ($total - $pasaron),
            ]
        );

        return $pasaron === $total ? 0 : 1;
    }
}
