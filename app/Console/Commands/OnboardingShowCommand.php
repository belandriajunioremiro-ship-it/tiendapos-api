<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tienda;
use App\Models\User;
use App\Models\Impuesto;
use App\Models\MetodoPago;
use App\Models\CategoriaProducto;
use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\TiendaMoneda;
use App\Models\TasaCambio;
use App\Models\TiendaOnboarding;
use App\Models\Suscripcion;
use App\Models\MargenGanancia;
use App\Models\ListaPrecio;
use App\Models\PlantillasImpresion;
use App\Models\ConfiguracionImpresora;
use App\Models\VarianteProducto;

class OnboardingShowCommand extends Command
{
    protected $signature   = 'onboarding:show {pais? : Código de país (VE, CO, MX, EC)}';
    protected $description = 'Muestra el detalle completo de una tienda onboardeada por país';

    public function handle(): void
    {
        $pais = strtoupper($this->argument('pais') ?? '');

        if (!$pais) {
            $pais = $this->choice(
                '¿Qué país quieres inspeccionar?',
                ['VE' => '🇻🇪 Venezuela', 'CO' => '🇨🇴 Colombia', 'MX' => '🇲🇽 México', 'EC' => '🇪🇨 Ecuador'],
                'VE'
            );
        }

        $pais = strtoupper($pais);

        // Buscar la tienda más reciente del país
        $tienda = Tienda::where('pais', $pais)->latest('id')->first();

        if (!$tienda) {
            $this->error("No se encontró ninguna tienda con pais = '{$pais}'.");
            $this->line(" → Ejecuta primero: <fg=yellow>php test_onboarding_{$pais}.php</>");
            return;
        }

        $info = $this->infoFlags($pais);

        // ── CABECERA ─────────────────────────────────────────────────
        $this->line('');
        $this->line('<fg=bright-cyan;bg=black;options=bold>================================================================================</>');
        $this->line('<fg=bright-cyan;bg=black;options=bold>         PRUEBA DE CONEXIÓN TIENDA POS DIRECTAMENTE DESDE NEON TCH              </>');
        $this->line('<fg=bright-cyan;bg=black;options=bold>================================================================================</>');
        $this->line(" {$info['bandera']} <fg=bright-green;options=bold>ONBOARDING DETALLE — {$pais} | {$tienda->nombre_comercial}</>");
        $this->line(" <fg=gray>{$info['autoridad']} · {$info['moneda']}</>");
        $this->line('<fg=bright-cyan>================================================================================</>');
        $this->line('');

        // ── DATOS DE LA TIENDA ────────────────────────────────────────
        $this->line('<fg=yellow;options=bold>🏢  DATOS FISCALES</>');
        $this->table([], [
            ['Campo',                    'Valor'],
            ['ID Tienda',                "#" . $tienda->id],
            [$info['id_label'],          $tienda->rif],
            ['Razón Social',             $tienda->razon_social],
            ['Nombre Comercial',         $tienda->nombre_comercial ?? '—'],
            ['Dirección',                $tienda->direccion ?? '—'],
            ['Teléfono',                 $tienda->telefono ?? '—'],
            ['Moneda Base',              $tienda->moneda_base],
            ['IGTF',                     $tienda->es_agente_igtf ? "✅ Agente IGTF {$tienda->alicuota_igtf}%" : '❌ No aplica'],
            ['País',                     $pais],
            ['Zona Horaria',             $tienda->zona_horaria ?? '—'],
        ]);

        // ── USUARIO ADMIN ─────────────────────────────────────────────
        $this->line('');
        $this->line('<fg=yellow;options=bold>👤  USUARIO ADMIN</>');
        $user = User::where('tienda_id', $tienda->id)->first();
        if ($user) {
            $this->table(['#', 'Nombre', 'Email', 'Activo'], [
                [$user->id, $user->name, $user->email, $user->activo ? '✅' : '❌'],
            ]);
        }

        // ── SUSCRIPCIÓN ────────────────────────────────────────────────
        $suscrip = Suscripcion::where('tienda_id', $tienda->id)->latest('id')->first();
        if ($suscrip) {
            $this->line('<fg=yellow;options=bold>📦  SUSCRIPCIÓN / PLAN</>');
            $this->table([], [
                ['Estado',    ucfirst($suscrip->estado)],
                ['Inicio',    optional($suscrip->inicio_trial)->format('d/m/Y') ?? '—'],
                ['Vence',     optional($suscrip->fin_trial)->format('d/m/Y') ?? '—'],
                ['Renovar',   $suscrip->auto_renovar ? 'Sí' : 'No'],
            ]);
        }

        // ── IMPUESTOS ─────────────────────────────────────────────────
        $this->line('');
        $this->line('<fg=yellow;options=bold>🧾  IMPUESTOS CONFIGURADOS</>');
        $impuestos = Impuesto::where('activo', true)->get();
        $rows = $impuestos->map(fn($i) => [
            $i->id,
            $i->nombre,
            $i->porcentaje . '%',
            ucfirst($i->tipo),
            $i->es_defecto ? '⭐ DEFAULT' : '',
        ])->toArray();
        $this->table(['ID', 'Nombre', 'Porcentaje', 'Tipo', 'Default'], $rows);

        // ── MONEDAS ───────────────────────────────────────────────────
        $this->line('<fg=yellow;options=bold>💱  MONEDAS HABILITADAS</>');
        $monedas = TiendaMoneda::all();
        $mRows = $monedas->map(fn($m) => [
            $m->moneda,
            $m->acepta_ventas  ? '✅' : '❌',
            $m->acepta_compras ? '✅' : '❌',
            $m->activa         ? '✅' : '❌',
        ])->toArray();
        $this->table(['Moneda', 'Ventas', 'Compras', 'Activa'], $mRows);

        // Tasa de cambio si aplica
        $tasa = TasaCambio::where('activa', true)->first();
        if ($tasa) {
            $this->line("   <fg=cyan>💱 Tasa: 1 {$tasa->moneda_base} = {$tasa->tasa} {$tasa->moneda_destino} (Fuente: {$tasa->fuente})</>");
        }

        // ── MÉTODOS DE PAGO ───────────────────────────────────────────
        $this->line('');
        $this->line('<fg=yellow;options=bold>💳  MÉTODOS DE PAGO</>');
        $metodos = MetodoPago::where('activo', true)->get();
        $mPRows = $metodos->map(fn($m) => [
            $m->nombre,
            $m->tipo,
            $m->moneda,
            $m->grava_igtf       ? '<fg=red>✅ IGTF</>' : '—',
            $m->requiere_referencia ? 'Sí'             : 'No',
        ])->toArray();
        $this->table(['Nombre', 'Tipo', 'Moneda', 'IGTF', 'Ref.'], $mPRows);

        // ── ALMACÉN / CAJA ────────────────────────────────────────────
        $this->line('<fg=yellow;options=bold>🏭  ALMACÉN Y CAJA</>');
        $almacen = Almacen::first();
        $caja    = Caja::first();
        $this->line("   Almacén : <fg=green>{$almacen->nombre}</> ({$almacen->tipo})");
        $this->line("   Caja    : <fg=green>{$caja->nombre}</>");

        // ── CATEGORÍAS ────────────────────────────────────────────────
        $this->line('');
        $this->line('<fg=yellow;options=bold>🗂️   CATEGORÍAS</>');
        $cats = CategoriaProducto::pluck('nombre')->toArray();
        $chunks = array_chunk($cats, 4);
        foreach ($chunks as $chunk) {
            $this->line('   • ' . implode('   • ', $chunk));
        }

        // ── CONFIGURACIÓN FISCAL DEL PAÍS ─────────────────────────────
        $this->line('');
        $this->line('<fg=yellow;options=bold>📋  CONFIGURACIÓN FISCAL DEL PAÍS</>');
        $this->table([], [
            ['País',              $info['nombre']],
            ['Autoridad Fiscal',  $info['autoridad']],
            ['Documento Fiscal',  $info['documento']],
            ['IVA General',       $info['iva']],
            ['Exenciones',        $info['exenciones']],
            ['Impuesto Especial', $info['especial']],
        ]);

        // ── PRIMER PRODUCTO ───────────────────────────────────────────
        $producto = Producto::where('codigo_sku', 'like', $pais . '-%')->latest('id')->first();
        if ($producto) {
            $this->line('');
            $this->line('<fg=yellow;options=bold>📦  PRIMER PRODUCTO REGISTRADO</>');
            $variante   = VarianteProducto::where('producto_id', $producto->id)->first();
            $inventario = $variante ? Inventario::where('variante_id', $variante->id)->first() : null;
            $impuesto   = Impuesto::find($producto->impuesto_id);
            $this->table([], [
                ['Campo',        'Valor'],
                ['Nombre',       $producto->nombre],
                ['SKU',          $producto->codigo_sku],
                ['Costo',        $producto->costo_promedio . ' ' . $tienda->moneda_base],
                ['Impuesto',     $impuesto->nombre ?? '—'],
                ['Stock',        ($inventario->cantidad_disponible ?? 0) . ' unidades'],
                ['Activo',       $producto->activo ? '✅' : '❌'],
            ]);
        }

        // ── ONBOARDING STATUS ─────────────────────────────────────────
        $this->line('');
        $ob = TiendaOnboarding::where('tienda_id', $tienda->id)->first();
        if ($ob) {
            $completado = $ob->completado ? '<fg=bright-green;options=bold>✅ COMPLETADO</>' : '<fg=red>⏳ PENDIENTE</>';
            $this->line("<fg=bright-cyan;options=bold>▶  Estado Onboarding:</> Paso {$ob->paso_actual} / 4  —  {$completado}");
        }

        $this->line('');
        $this->line('<fg=bright-cyan>================================================================================</>');
        $this->line(" <fg=green;options=bold>✅ Tienda #{$tienda->id} — {$pais} auditada correctamente desde NEON TCH</>");
        $this->line('<fg=bright-cyan>================================================================================</>');
        $this->line('');
    }

    private function infoFlags(string $pais): array
    {
        return match($pais) {
            'VE' => [
                'bandera'    => '🇻🇪',
                'nombre'     => 'Venezuela',
                'autoridad'  => 'SENIAT',
                'documento'  => 'Factura Fiscal SENIAT',
                'id_label'   => 'RIF',
                'moneda'     => 'USD principal + VES secundario',
                'iva'        => '16% general | 8% reducido',
                'exenciones' => 'Cesta básica (Decreto Ejecutivo)',
                'especial'   => 'IGTF 3% sobre pagos en divisas',
            ],
            'CO' => [
                'bandera'    => '🇨🇴',
                'nombre'     => 'Colombia',
                'autoridad'  => 'DIAN',
                'documento'  => 'Factura Electrónica DIAN',
                'id_label'   => 'NIT',
                'moneda'     => 'COP (Peso Colombiano)',
                'iva'        => '19% general | 5% diferencial',
                'exenciones' => 'Canasta familiar (art. 477 ET)',
                'especial'   => 'INC bebidas / Retefuente',
            ],
            'MX' => [
                'bandera'    => '🇲🇽',
                'nombre'     => 'México',
                'autoridad'  => 'SAT',
                'documento'  => 'CFDI 4.0 (SAT)',
                'id_label'   => 'RFC',
                'moneda'     => 'MXN (Peso Mexicano)',
                'iva'        => '16% general | 0% alimentos',
                'exenciones' => 'Alimentos sin procesar / medicamentos (LIVA art. 2-A)',
                'especial'   => 'IEPS (bebidas / tabaco / combustible)',
            ],
            'EC' => [
                'bandera'    => '🇪🇨',
                'nombre'     => 'Ecuador',
                'autoridad'  => 'SRI',
                'documento'  => 'Comprobante Electrónico SRI',
                'id_label'   => 'RUC',
                'moneda'     => 'USD (dolarizado desde 2000)',
                'iva'        => '15% general | 0% medicamentos',
                'exenciones' => 'Medicamentos, canasta básica (LRTI)',
                'especial'   => 'ICE (consumos especiales)',
            ],
            default => [
                'bandera'    => '🌍',
                'nombre'     => $pais,
                'autoridad'  => 'N/A',
                'documento'  => 'Factura',
                'id_label'   => 'Tax ID',
                'moneda'     => 'USD',
                'iva'        => 'N/A',
                'exenciones' => 'N/A',
                'especial'   => 'N/A',
            ],
        };
    }
}
