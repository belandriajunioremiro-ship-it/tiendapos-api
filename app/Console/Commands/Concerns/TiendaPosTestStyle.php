<?php

namespace App\Console\Commands\Concerns;

trait TiendaPosTestStyle
{
    protected function testClear(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls 2>NUL');
        } else {
            system('clear 2>/dev/null');
        }
    }

    protected function testHeader(string $titulo, string $subtitulo = ''): void
    {
        $this->testClear();
        $linea = str_repeat('═', 70);
        $this->line("");
        $this->line("<fg=bright-cyan;bg=black;options=bold>" . $linea . "</>");
        $this->line("<fg=bright-cyan;bg=black;options=bold>   PRUEBA DE CONEXIÓN TIENDA POS DIRECTAMENTE DESDE NEON TECH   </>");
        $this->line("<fg=bright-cyan;bg=black;options=bold>" . $linea . "</>");
        $this->line("<fg=bright-yellow;options=bold>  🧪 " . strtoupper($titulo) . "</>");
        if ($subtitulo) {
            $this->line("<fg=gray>  " . $subtitulo . "</>");
        }
        $this->line("<fg=bright-cyan>" . $linea . "</>");
        $this->line("");
    }

    protected function testStep(int $numero, string $descripcion): void
    {
        $this->line("");
        $this->line("<fg=bright-yellow;options=bold>" . $numero . "️⃣  " . $descripcion . "</>");
        $this->line("<fg=gray>   " . str_repeat('─', 60) . "</>");
    }

    protected function testOk(string $mensaje): void
    {
        $this->line("   <fg=green;options=bold>✓</> <fg=green>" . $mensaje . "</>");
    }

    protected function testFail(string $mensaje): void
    {
        $this->line("   <fg=red;options=bold>✗</> <fg=red>" . $mensaje . "</>");
    }

    protected function testInfo(string $mensaje): void
    {
        $this->line("   <fg=blue>ℹ</> <fg=gray>" . $mensaje . "</>");
    }

    protected function testDetail(string $clave, string $valor): void
    {
        $this->line("      <fg=cyan>" . str_pad($clave, 25) . "</><fg=white>" . $valor . "</>");
    }

    protected function testFooter(string $mensaje, bool $ok = true, array $resumen = []): void
    {
        $linea = str_repeat('═', 70);
        $this->line("");
        $this->line("<fg=bright-cyan>" . $linea . "</>");

        if (!empty($resumen)) {
            foreach ($resumen as $k => $v) {
                $this->line("<fg=white>  " . str_pad($k, 35) . "<fg=green>" . $v . "</>");
            }
            $this->line("<fg=bright-cyan>" . str_repeat('─', 70) . "</>");
        }

        if ($ok) {
            $this->line("<fg=black;bg=green;options=bold>  ✅  " . strtoupper($mensaje) . "  ✅  </>");
        } else {
            $this->line("<fg=white;bg=red;options=bold>  ❌  " . strtoupper($mensaje) . "  ❌  </>");
        }
        $this->line("<fg=bright-cyan>" . $linea . "</>");
        $this->line("");
    }
}
