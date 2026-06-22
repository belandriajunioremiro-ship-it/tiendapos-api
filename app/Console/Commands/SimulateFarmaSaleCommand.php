<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PosBusinessRulesService;
use App\Models\Venta;
use App\Models\SesionCaja;
use App\Models\Tienda;
use App\Models\Caja;
use App\Models\User;
use App\Models\Cliente;
use App\Models\VarianteProducto;
use App\Models\MetodoPago;
use App\Models\TasaCambio;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\VarianteProducto as Variante;
use App\Models\Inventario;
use App\Models\Unidad;

class SimulateFarmaSaleCommand extends Command
{
    protected $signature = 'pos:farma-sale';
    protected $description = 'Simula una venta de farmacia con medicamentos exentos e impuesto IVA/IGTF en Venezuela 2026';

    protected PosBusinessRulesService $posService;

    public function __construct(PosBusinessRulesService $posService)
    {
        parent::__construct();
        $this->posService = $posService;
    }

    public function handle()
    {
        $this->line('');
        $this->line('<fg=bright-cyan;bg=black;options=bold>   PRUEBA DE CONEXIÓN TIENDA POS DIRECTAMENTE DESDE NEON TCH   </>');
        $this->line('<fg=bright-green;options=bold> 💊 FARMACIA SANTA MARÍA 2026 - VENEZUELA (IVA 0% / 16% + IGTF 3%) </>');
        $this->line('<fg=bright-cyan>================================================================</>');
        $this->line(' <fg=gray>Medicamentos Esenciales (Exentos) vs OTC/Cosméticos (Gravables 16%)</>');
        $this->line('<fg=bright-cyan>================================================================</>');
        $this->line('');

        $tienda = Tienda::first();
        if (!$tienda) {
            $this->error('Debe correr el DemoPosSeeder primero.');
            return;
        }

        $tasaVES = TasaCambio::where('moneda_destino', 'VES')->where('activa', true)->first()->tasa ?? 592.51;

        // Asegurar que existan productos de farmacia con precios 2026
        $this->ensureFarmaProducts();

        $this->info('1️⃣ CONFIGURANDO SESIÓN DE CAJA (FARMACIA)...');
        $caja = Caja::first();
        $usuario = User::first();
        $cliente = Cliente::first();

        $sesion = SesionCaja::firstOrCreate(
            ['caja_id' => $caja->id, 'estado' => 'abierta'],
            ['user_id' => $usuario->id, 'apertura_en' => now()]
        );

        $venta = Venta::create([
            'numero_factura' => 'FARMA-' . time(),
            'caja_id'        => $caja->id,
            'sesion_caja_id' => $sesion->id,
            'user_id'        => $usuario->id,
            'cliente_id'     => $cliente->id,
            'almacen_id'     => \App\Models\Almacen::first()->id,
            'moneda_factura' => 'USD',
            'estado'         => 'borrador',
            'tipo_documento' => 'FAC',
            'tasa_referencia'=> $tasaVES,
        ]);

        $this->line("   <fg=green>✓</> Ticket Farmacia creado: {$venta->numero_factura}");

        // Helper para agregar items
        $agregar = function (string $nombre, float $cantidad, float $ivaPct) use (&$venta) {
            $var = VarianteProducto::whereHas('producto', fn ($q) => $q->where('nombre', $nombre))->first();
            if (!$var) {
                $this->warn("   ⚠️  No encontrado: {$nombre}");
                return;
            }
            $venta = $this->posService->agregarItemVenta($venta, $var, $cantidad, $var->producto->precio_base, 1.0, 0, $ivaPct);
            $badge = $ivaPct > 0 ? '<fg=red>(G 16%)</>' : '<fg=green>(EXENTO)</>';
            $this->line("   🧪 {$cantidad} x {$nombre} @ $" . number_format($var->producto->precio_base, 2) . " {$badge}");
        };

        $this->info("\n2️⃣ ESCANEANDO MEDICAMENTOS Y PRODUCTOS...");

        // Exentos: medicamentos esenciales decreto Venezuela
        $agregar('Paracetamol 500mg x 100 Tabs',    1, 0);
        $agregar('Amoxicilina 500mg x 15 Caps',      1, 0);

        // Gravables 16%: OTC, vitaminas, accesorios
        $agregar('Vitamina C 1g x 10 Efervescentes', 2, 16);
        $agregar('Termómetro Digital',                1, 16);

        $venta->refresh();
        $this->line("   <fg=bright-yellow;options=bold>=> Total con IVA (antes de pagos): $" . number_format($venta->total, 2) . "</>");

        // Pagos
        $this->info("\n3️⃣ PROCESANDO PAGOS MIXTOS (IGTF SOBRE DIVISA)...");

        $efectivoUSD = MetodoPago::where('nombre', 'Efectivo USD')->first();
        $zelle       = MetodoPago::where('nombre', 'Zelle')->first();
        $pagoMovil   = MetodoPago::where('nombre', 'Pago Móvil VES')->first();

        // $10 Efectivo USD → IGTF = 3% de $10 = $0.30
        $pago1 = 10.00;
        $this->posService->registrarPagoConIgtf($venta, $efectivoUSD, $pago1, 1.0);
        $this->line("   💵 Efectivo USD:   $" . number_format($pago1, 2) . "  <fg=magenta>(+ IGTF: $" . number_format($pago1 * 0.03, 2) . ")</>");

        // $5 Zelle → IGTF = 3% de $5 = $0.15
        $pago2 = 5.00;
        $this->posService->registrarPagoConIgtf($venta, $zelle, $pago2, 1.0);
        $this->line("   💳 Zelle:          $" . number_format($pago2, 2) . "  <fg=magenta>(+ IGTF: $" . number_format($pago2 * 0.03, 2) . ")</>");

        // Resto en Pago Móvil VES (no grava IGTF)
        $venta->refresh();
        $restoUSD = $venta->total - $venta->pagos()->sum('monto_en_factura');
        $restoVES = round($restoUSD * $tasaVES, 2);
        $this->posService->registrarPagoConIgtf($venta, $pagoMovil, $restoVES, 1 / $tasaVES);
        $this->line("   📱 Pago Móvil VES: Bs. " . number_format($restoVES, 2) . "  <fg=blue>(Tasa: {$tasaVES})</>");

        // Cierre de venta
        $this->info("\n4️⃣ IMPRIMIENDO TICKET FISCAL...");
        $venta = $this->posService->procesarCobroFactura($venta);
        $venta->refresh();

        // Calcular bases
        $exento = 0; $baseImponible = 0;
        foreach ($venta->items as $item) {
            $lineaBruta = $item->precio_en_factura * $item->cantidad;
            if ($item->impuesto_monto > 0) {
                $baseImponible += $lineaBruta;
            } else {
                $exento += $lineaBruta;
            }
        }

        // Impresión del recibo
        $this->line("\n");
        $this->line("<fg=black;bg=white>                                                                  </>");
        $this->line("<fg=black;bg=white;options=bold>               💊  FARMACIA SANTA MARÍA C.A.  💊               </>");
        $this->line("<fg=black;bg=white>                      RIF: J-30158742-4                          </>");
        $this->line("<fg=black;bg=white>              Av. Bolívar, Local 4, Caracas — 0212-555-1234       </>");
        $this->line("<fg=black;bg=white>                                                                  </>");
        $this->line("<fg=black;bg=white>  Factura: {$venta->numero_factura}                                            </>");
        $this->line("<fg=black;bg=white>  Fecha:   " . now()->format('d/m/Y H:i:s') . "                               </>");
        $this->line("<fg=black;bg=white>  Atendido por: {$usuario->name}                                   </>");
        $this->line("<fg=black;bg=white>----------------------------------------------------------------  </>");

        $rows = [];
        foreach ($venta->items as $item) {
            $desc  = substr($item->variante->producto->nombre, 0, 26);
            $badge = $item->impuesto_monto > 0 ? '(G)' : '(E)';
            $rows[] = [
                $item->cantidad,
                $desc,
                '$' . number_format($item->precio_en_factura, 2),
                $badge,
                '$' . number_format($item->total_linea, 2),
            ];
        }
        $this->table(['Cant', 'Descripción', 'P.Unit', 'T', 'Total USD'], $rows);

        $this->line("<fg=black;bg=white>  (E) Medicamentos Esenciales — EXENTOS IVA                       </>");
        $this->line("<fg=black;bg=white>  (G) OTC / Cosméticos / Accesorios — IVA 16%                     </>");
        $this->line("<fg=black;bg=white>----------------------------------------------------------------  </>");
        $this->line(sprintf("<fg=black;bg=white>  SUBTOTAL EXENTO:                                $ %8s   </>", number_format($exento, 2)));
        $this->line(sprintf("<fg=black;bg=white>  BASE IMPONIBLE (GRAVABLE):                      $ %8s   </>", number_format($baseImponible, 2)));
        $this->line(sprintf("<fg=black;bg=white>  IVA ALÍCUOTA GENERAL (16%%):                    $ %8s   </>", number_format($venta->impuesto_iva, 2)));
        $this->line(sprintf("<fg=black;bg=white>  IGTF PERCIBIDO (3%% s/ divisas pagadas):         $ %8s   </>", number_format($venta->impuesto_igtf, 2)));
        $this->line("<fg=black;bg=white>----------------------------------------------------------------  </>");
        $this->line(sprintf("<fg=black;bg=white;options=bold>  TOTAL A PAGAR:                                  $ %8s   </>", number_format($venta->total, 2)));
        $this->line(sprintf("<fg=black;bg=white;options=bold>  TOTAL EN Bs. (BCV %.2f):                   Bs %10s   </>", $tasaVES, number_format($venta->total * $tasaVES, 2)));
        $this->line("<fg=black;bg=white>----------------------------------------------------------------  </>");
        $this->line("<fg=black;bg=white>  MEDIOS DE PAGO:                                                  </>");
        foreach ($venta->pagos as $pago) {
            $igtfStr = $pago->monto_igtf > 0 ? " (IGTF: $" . number_format($pago->monto_igtf, 2) . ")" : "";
            $this->line(sprintf("<fg=black;bg=white>  %-22s %12s %-3s %-12s   </>",
                $pago->metodoPago->nombre,
                number_format($pago->monto_pago, 2),
                $pago->moneda_pago,
                $igtfStr
            ));
        }
        $this->line("<fg=black;bg=white>                                                                  </>");
        $this->line("<fg=black;bg=white;options=bold>         ¡GRACIAS POR CONFIAR EN FARMACIA SANTA MARÍA!          </>");
        $this->line("<fg=black;bg=white>                  Conserve su factura para reclamaciones            </>");
        $this->line("<fg=black;bg=white>                                                                  </>");
        $this->line('');
        $this->info("✨ ¡Venta Farmacia (IVA Diferenciado + IGTF) Completada con Éxito!");
    }

    /**
     * Crea los productos de farmacia si no existen en la BD.
     * Precios USD referenciados al mercado venezolano 2026.
     */
    private function ensureFarmaProducts(): void
    {
        $cat = CategoriaProducto::firstOrCreate(
            ['slug' => 'farmacia-2026'],
            ['nombre' => 'Farmacia 2026', 'nivel' => 1, 'ruta' => '10']
        );

        $uUnd = Unidad::where('abreviatura', 'Und')->first();
        $almacen = \App\Models\Almacen::first();

        $items = [
            // Medicamentos Esenciales EXENTOS (IVA 0%)
            ['sku' => 'FARMA-P01', 'nombre' => 'Paracetamol 500mg x 100 Tabs',    'precio' => 3.50,  'costo' => 2.00],
            ['sku' => 'FARMA-P02', 'nombre' => 'Amoxicilina 500mg x 15 Caps',     'precio' => 5.80,  'costo' => 3.50],
            ['sku' => 'FARMA-P03', 'nombre' => 'Ibuprofeno 400mg x 10 Tabs',      'precio' => 2.50,  'costo' => 1.40],
            ['sku' => 'FARMA-P04', 'nombre' => 'Losartán 50mg x 30 Tabs',         'precio' => 7.00,  'costo' => 4.50],
            ['sku' => 'FARMA-P05', 'nombre' => 'Omeprazol 20mg x 14 Caps',        'precio' => 4.20,  'costo' => 2.50],
            // OTC / Cosméticos GRAVABLES (IVA 16%)
            ['sku' => 'FARMA-P06', 'nombre' => 'Vitamina C 1g x 10 Efervescentes','precio' => 3.80,  'costo' => 2.20],
            ['sku' => 'FARMA-P07', 'nombre' => 'Alcohol Antiséptico 250ml',        'precio' => 2.00,  'costo' => 1.00],
            ['sku' => 'FARMA-P08', 'nombre' => 'Protector Solar SPF50 50g',        'precio' => 8.50,  'costo' => 5.00],
            ['sku' => 'FARMA-P09', 'nombre' => 'Termómetro Digital',               'precio' => 6.00,  'costo' => 3.50],
        ];

        foreach ($items as $item) {
            if (Producto::where('codigo_sku', $item['sku'])->exists()) continue;

            $margen = (($item['precio'] / $item['costo']) - 1) * 100;
            $prod = Producto::create([
                'codigo_sku'     => $item['sku'],
                'nombre'         => $item['nombre'],
                'categoria_id'   => $cat->id,
                'unidad_id'      => $uUnd->id,
                'moneda_precio'  => 'USD',
                'costo_promedio' => $item['costo'],
                'margen_pct'     => $margen,
            ]);

            $var = VarianteProducto::create([
                'producto_id'  => $prod->id,
                'codigo_barra' => $item['sku'] . '-BAR',
                'descripcion'  => $item['nombre'] . ' (Default)',
                'factor_unidad'=> 1,
            ]);

            Inventario::create([
                'variante_id'         => $var->id,
                'almacen_id'          => $almacen->id,
                'cantidad_disponible' => 500,
                'costo_promedio'      => $item['costo'],
            ]);
        }
    }
}
