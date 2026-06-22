<?php

namespace App\Console\Commands;

use App\Models\Tienda;
use App\Models\User;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\CategoriaProducto;
use App\Models\Unidad;
use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Inventario;
use App\Models\CuentaCredito;
use App\Models\Impuesto;
use App\Models\Proveedor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MultitenancyTestCommand extends Command
{
    protected $signature = 'multitenancy:test 
                            {--cleanup : Solo limpiar datos de prueba previos}
                            {--detalle : Mostrar detalle completo de cada paso}';

    protected $description = 'Prueba integral de aislamiento multi-tenancy entre tiendas';

    private array $createdIds = [];

    public function handle(): int
    {
        if ($this->option('cleanup')) {
            return $this->cleanupOnly();
        }

        $this->banner();

        $tiendaA = Tienda::first();
        if (! $tiendaA) {
            $this->error('  No existe ninguna tienda. Corre el seeder primero.');
            return self::FAILURE;
        }

        $verbose = $this->option('detalle');

        // ── PASO 1: Crear Tienda B ──────────────────────────────────
        $this->line('<fg=bright-yellow;options=bold>  PASO 1/7</>  Creando Tienda B de prueba...');
        $tiendaB = Tienda::create([
            'pais'            => 'CO',
            'rif'             => 'NIT-MULTITENANT-TEST',
            'razon_social'    => 'Tienda B Test Multi-tenancy',
            'nombre_comercial'=> 'Tienda B Test',
            'moneda_base'     => 'COP',
            'zona_horaria'    => 'America/Bogota',
            'prefijo_factura' => 'MTB',
            'siguiente_numero'=> 1,
            'es_agente_igtf'  => false,
            'activo'         => true,
        ]);
        $this->createdIds['tiendaB'] = $tiendaB->id;

        $this->line("  <fg=green>✓</> Tienda B creada — ID: <fg=bright-green>{$tiendaB->id}</> | <fg=cyan>{$tiendaB->razon_social}</>");
        $this->line('');

        // ── PASO 2: Crear usuario Tienda B ─────────────────────────
        $this->line('<fg=bright-yellow;options=bold>  PASO 2/7</>  Creando usuario para Tienda B...');
        $userB = User::firstOrCreate(
            ['email' => 'multitenancy-test-b@tiendapos.com'],
            [
                'tienda_id' => $tiendaB->id,
                'name'      => 'Admin Tienda B Test',
                'password'  => bcrypt('Test1234'),
                'activo'    => true,
            ]
        );
        $userB->assignRole('admin');
        $this->createdIds['userB'] = $userB->id;

        $this->line("  <fg=green>✓</> Usuario B — <fg=cyan>{$userB->email}</> (tienda_id: <fg=bright-green>{$userB->tienda_id}</>)");
        $this->line('');

        // ── PASO 3: Login como Tienda B y crear datos ───────────────
        $this->line('<fg=bright-yellow;options=bold>  PASO 3/7</>  Autenticando como Tienda B y creando datos...');
        Auth::login($userB);

        $catB = CategoriaProducto::create([
            'nombre' => 'Categoría Secreta Tienda B',
            'slug'  => 'cat-secreta-b',
        ]);

        $prodB = Producto::create([
            'categoria_id'    => $catB->id,
            'unidad_id'       => 1,
            'moneda_precio'   => 'COP',
            'codigo_sku'      => 'SECRET-B-' . time(),
            'nombre'          => 'Producto Secreto Tienda B ☣️',
            'costo_promedio'  => 5000,
            'margen_pct'      => 20,
        ]);

        $clienteB = Cliente::create([
            'tipo_documento'   => 'CC',
            'numero_documento' => '99999999',
            'nombre'           => 'Cliente Secreto Tienda B',
            'tipo_cliente'     => 'natural',
        ]);

        $this->createdIds = array_merge($this->createdIds, [
            'catB'     => $catB->id,
            'prodB'    => $prodB->id,
            'clienteB' => $clienteB->id,
        ]);

        $this->line("  <fg=green>✓</> Auth como: <fg=cyan>{$userB->email}</>");
        $this->line("  <fg=green>✓</> Categoría B — tienda_id: <fg=bright-green>{$catB->tienda_id}</>");
        $this->line("  <fg=green>✓</> Producto B — tienda_id: <fg=bright-green>{$prodB->tienda_id}</>");
        $this->line("  <fg=green>✓</> Cliente B — tienda_id: <fg=bright-green>{$clienteB->tienda_id}</>");

        if ($verbose) {
            $this->line('');
            $this->line('  <fg=gray>Detalle de inyección automática:</>');
            $this->table(
                ['Modelo', 'ID', 'tienda_id', 'Nombre'],
                [
                    ['CategoriaProducto', $catB->id, $catB->tienda_id, $catB->nombre],
                    ['Producto',          $prodB->id, $prodB->tienda_id, $prodB->nombre],
                    ['Cliente',           $clienteB->id, $clienteB->tienda_id, $clienteB->nombre],
                ]
            );
        }
        $this->line('');

        // ── PASO 4: Cambiar a Tienda A ─────────────────────────────
        $this->line('<fg=bright-yellow;options=bold>  PASO 4/7</>  Cambiando contexto a Tienda A...');
        $userA = User::where('tienda_id', $tiendaA->id)->first();
        Auth::login($userA);

        $this->line("  <fg=green>✓</> Auth como: <fg=cyan>{$userA->email}</> (tienda_id: <fg=bright-green>{$userA->tienda_id}</>)");
        $this->line('');

        // ── PASO 5: Test de aislamiento ────────────────────────────
        $this->line('<fg=bright-yellow;options=bold>  PASO 5/7</>  Ejecutando pruebas de aislamiento...');
        $this->line('');

        $tests = [];

        // Test 1: Productos
        $prodVisible = Producto::where('nombre', 'Producto Secreto Tienda B ☣️')->first();
        $prodSinScope = Producto::withoutGlobalScope('tienda')->where('nombre', 'Producto Secreto Tienda B ☣️')->first();
        $tests['Producto'] = $prodVisible === null && $prodSinScope !== null;

        // Test 2: Clientes
        $clienteVisible = Cliente::where('nombre', 'Cliente Secreto Tienda B')->first();
        $clienteSinScope = Cliente::withoutGlobalScope('tienda')->where('nombre', 'Cliente Secreto Tienda B')->first();
        $tests['Cliente'] = $clienteVisible === null && $clienteSinScope !== null;

        // Test 3: Categorías
        $catVisible = CategoriaProducto::where('nombre', 'Categoría Secreta Tienda B')->first();
        $catSinScope = CategoriaProducto::withoutGlobalScope('tienda')->where('nombre', 'Categoría Secreta Tienda B')->first();
        $tests['Categoría'] = $catVisible === null && $catSinScope !== null;

        // Imprimir resultados
        $this->line('  <fg=bright-cyan>┌──────────────────┬───────────────────────────────┬───────────────────────────────┐</>');
        $this->line('  <fg=bright-cyan>│</> <fg=bright-white;options=bold>Modelo           </> <fg=bright-cyan>│</> <fg=bright-white;options=bold>Visible con Scope (Aislado)   </> <fg=bright-cyan>│</> <fg=bright-white;options=bold>Visible sin Scope (Existe)    </> <fg=bright-cyan>│</>');
        $this->line('  <fg=bright-cyan>├──────────────────┼───────────────────────────────┼───────────────────────────────┤</>');

        $modelData = [
            'Producto'  => [$prodVisible, $prodSinScope],
            'Cliente'   => [$clienteVisible, $clienteSinScope],
            'Categoría' => [$catVisible, $catSinScope],
            'Unidad'    => [$undVisible, $undSinScope],
        ];

        foreach ($modelData as $label => [$conScope, $sinScope]) {
            $aislado = $conScope === null
                ? '<fg=bright-green;options=bold>  ✅ No visible (OK)          </>'
                : '<fg=bright-red;options=bold>  ❌ VISIBLE (FUGA!)          </>';

            $existe = $sinScope !== null
                ? '<fg=bright-green;options=bold>  ✅ Existe en DB (OK)        </>'
                : '<fg=bright-red;options=bold>  ❌ No existe (ERROR)        </>';

            $this->line("  <fg=bright-cyan>│</> <fg=bright-white>{$label}       </> <fg=bright-cyan>│</>{$aislado} <fg=bright-cyan>│</>{$existe} <fg=bright-cyan>│</>");
        }

        $this->line('  <fg=bright-cyan>└──────────────────┴───────────────────────────────┴───────────────────────────────┘</>');
        $this->line('');

        // ── PASO 6: Conteo comparativo ──────────────────────────────
        $this->line('<fg=bright-yellow;options=bold>  PASO 6/7</>  Conteo comparativo de registros...');
        $this->line('');

        $modelos = [
            'Productos'     => Producto::class,
            'Clientes'      => Cliente::class,
            'Categorías'    => CategoriaProducto::class,
            'Unidades'      => Unidad::class,
            'Almacenes'     => Almacen::class,
            'Cajas'         => Caja::class,
            'Inventario'    => Inventario::class,
            'Impuestos'     => Impuesto::class,
            'Proveedores'   => Proveedor::class,
            'Créditos'      => CuentaCredito::class,
        ];

        $rows = [];
        foreach ($modelos as $label => $model) {
            $conScope = $model::count();
            $sinScope = $model::withoutGlobalScope('tienda')->count();
            $diferencia = $sinScope - $conScope;
            $diffStr = $diferencia > 0
                ? "<fg=bright-yellow>+{$diferencia}</>"
                : '<fg=gray>0</>';

            $rows[] = [$label, $conScope, $sinScope, $diffStr];
        }

        $this->table(
            ['Modelo', 'Tienda A (scope)', 'Total DB (sin scope)', 'Aislados'],
            $rows
        );
        $this->line('');

        // ── PASO 7: Veredicto ───────────────────────────────────────
        $this->line('<fg=bright-yellow;options=bold>  PASO 7/7</>  Veredicto final...');
        $this->line('');

        $fallaron = array_filter($tests, fn($p) => !$p);
        $pasaron = array_filter($tests, fn($p) => $p);

        if (count($fallaron) === 0) {
            $this->line('  <fg=bright-green;options=bold>┌─────────────────────────────────────────────────────────────────────────────┐</>');
            $this->line('  <fg=bright-green;options=bold>│</>                                                                             <fg=bright-green;options=bold>│</>');
            $this->line('  <fg=bright-green;options=bold>│</>  <fg=bright-white;options=bold>🔒  MULTI-TENANCY 100% VERIFICADO — ' . count($pasaron) . '/' . count($tests) . ' PRUEBAS PASADAS       </> <fg=bright-green;options=bold>│</>');
            $this->line('  <fg=bright-green;options=bold>│</>                                                                             <fg=bright-green;options=bold>│</>');
            $this->line('  <fg=bright-green;options=bold>│</>  <fg=white>Las tiendas NO pueden ver datos entre sí. El aislamiento es total.</>      <fg=bright-green;options=bold>│</>');
            $this->line('  <fg=bright-green;options=bold>│</>  <fg=white>El trait BelongsToTienda inyecta y filtra tienda_id automáticamente.</>  <fg=bright-green;options=bold>│</>');
            $this->line('  <fg=bright-green;options=bold>│</>                                                                             <fg=bright-green;options=bold>│</>');
            $this->line('  <fg=bright-green;options=bold>└─────────────────────────────────────────────────────────────────────────────┘</>');
        } else {
            $this->line('  <fg=bright-red;options=bold>┌─────────────────────────────────────────────────────────────────────────────┐</>');
            $this->line('  <fg=bright-red;options=bold>│</>                                                                             <fg=bright-red;options=bold>│</>');
            $this->line('  <fg=bright-red;options=bold>│</>  <fg=bright-white;options=bold>🚨  MULTI-TENANCY FALLÓ — ' . count($fallaron) . ' FUGA(S) DETECTADA(S)            </> <fg=bright-red;options=bold>│</>');
            $this->line('  <fg=bright-red;options=bold>│</>                                                                             <fg=bright-red;options=bold>│</>');
            foreach ($fallaron as $modelo => $_) {
                $this->line("  <fg=bright-red;options=bold>│</>  <fg=bright-red>❌ {$modelo}: Tienda A puede ver datos de Tienda B</>                <fg=bright-red;options=bold>│</>");
            }
            $this->line('  <fg=bright-red;options=bold>│</>                                                                             <fg=bright-red;options=bold>│</>');
            $this->line('  <fg=bright-red;options=bold>└─────────────────────────────────────────────────────────────────────────────┘</>');
        }
        $this->line('');

        // ── LIMPIEZA ────────────────────────────────────────────────
        $this->cleanup();
        $this->line('');
        $this->line('<fg=bright-cyan>================================================================================</>');
        $this->line('  <fg=green;options=bold>✓ Test completado y datos de prueba eliminados.</>');
        $this->line('<fg=bright-cyan>================================================================================</>');
        $this->line('');

        return count($fallaron) === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function banner(): void
    {
        $this->line('');
        $this->line('<fg=bright-cyan;options=bold>================================================================================</>');
        $this->line('<fg=bright-cyan;options=bold>                                                                                </>');
        $this->line('<fg=bright-cyan;options=bold>          🔒  PRUEBA DE CONEXIÓN TIENDA POS DIRECTAMENTE DESDE NEON TCH  🔒       </>');
        $this->line('<fg=bright-cyan;options=bold>          Aislamiento de datos entre tiendas SaaS                                </>');
        $this->line('<fg=bright-cyan;options=bold>                                                                                </>');
        $this->line('<fg=bright-cyan;options=bold>================================================================================</>');
        $this->line('');
    }

    private function cleanup(): void
    {
        $this->line('');
        $this->line('<fg=gray>  🧹 Limpiando datos de prueba...</>');

        Auth::login(User::where('email', 'multitenancy-test-b@tiendapos.com')->first());

        if (!empty($this->createdIds['prodB'])) {
            Producto::withoutGlobalScope('tienda')->where('id', $this->createdIds['prodB'])->delete();
        }
        if (!empty($this->createdIds['clienteB'])) {
            Cliente::withoutGlobalScope('tienda')->where('id', $this->createdIds['clienteB'])->delete();
        }
        if (!empty($this->createdIds['catB'])) {
            CategoriaProducto::withoutGlobalScope('tienda')->where('id', $this->createdIds['catB'])->delete();
        }
        if (!empty($this->createdIds['userB'])) {
            User::where('id', $this->createdIds['userB'])->delete();
        }
        if (!empty($this->createdIds['tiendaB'])) {
            Tienda::where('id', $this->createdIds['tiendaB'])->delete();
        }

        Auth::logout();

        $this->line('  <fg=green>✓</> Datos eliminados.');
    }

    private function cleanupOnly(): int
    {
        $this->line('');
        $this->line('<fg=bright-yellow>  🧹 Modo limpieza...</>');

        $deleted = 0;

        $deleted += Producto::withoutGlobalScope('tienda')
            ->where('nombre', 'Producto Secreto Tienda B ☣️')->delete();
        $deleted += Cliente::withoutGlobalScope('tienda')
            ->where('nombre', 'Cliente Secreto Tienda B')->delete();
        $deleted += CategoriaProducto::withoutGlobalScope('tienda')
            ->where('nombre', 'Categoría Secreta Tienda B')->delete();
        $deleted += User::where('email', 'multitenancy-test-b@tiendapos.com')->delete();
        $deleted += Tienda::where('rif', 'NIT-MULTITENANT-TEST')->delete();

        $this->line("  <fg=green>✓</> {$deleted} registros eliminados.");
        $this->line('');

        return self::SUCCESS;
    }
}
