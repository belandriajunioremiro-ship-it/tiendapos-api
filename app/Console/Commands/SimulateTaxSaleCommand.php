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

class SimulateTaxSaleCommand extends Command
{
    protected $signature = 'pos:tax-sale';
    protected $description = 'Simula una venta con impuestos IVA (Exentos y Gravables) e IGTF en Venezuela';

    protected $posService;

    public function __construct(PosBusinessRulesService $posService)
    {
        parent::__construct();
        $this->posService = $posService;
    }

    public function handle()
    {
        $this->line('');
        $this->line('<fg=bright-cyan;bg=black;options=bold>   PRUEBA DE CONEXIÓN TIENDA POS DIRECTAMENTE DESDE NEON TCH   </>');
        $this->line('<fg=bright-magenta;options=bold> 🧾 SIMULADOR FISCAL VENEZUELA 2026 (IVA 16% + IGTF 3%) </>');
        $this->line('<fg=bright-cyan>===============================================================</>');
        $this->line(' <fg=gray>Productos de Cesta Básica (Exentos) vs Procesados (Gravables 16%)</>');
        $this->line('<fg=bright-cyan>===============================================================</>');
        $this->line('');

        $tienda = Tienda::first();
        if (!$tienda) {
            $this->error('Debe correr el DemoPosSeeder primero.');
            return;
        }

        $tasaVES = TasaCambio::where('moneda_destino', 'VES')->where('activa', true)->first()->tasa ?? 592.51;

        $this->info('1️⃣ CONFIGURANDO SESIÓN Y TICKET...');
        $caja = Caja::first();
        $usuario = User::first() ?? User::factory()->create();
        $cliente = Cliente::first();

        $sesion = SesionCaja::firstOrCreate(
            ['caja_id' => $caja->id, 'estado' => 'abierta'],
            ['user_id' => $usuario->id, 'apertura_en' => now()]
        );

        $venta = Venta::create([
            'numero_factura' => 'TAX-' . time(),
            'caja_id' => $caja->id,
            'sesion_caja_id' => $sesion->id,
            'user_id' => $usuario->id,
            'cliente_id' => $cliente->id,
            'almacen_id' => \App\Models\Almacen::first()->id,
            'moneda_factura' => 'USD',
            'estado' => 'borrador',
            'tipo_documento' => 'FAC',
            'tasa_referencia' => $tasaVES
        ]);

        $this->line("   <fg=green>✓</> Ticket Fiscal Creado: {$venta->numero_factura}");

        $this->info("\n2️⃣ PROCESANDO PRODUCTOS (EXENTOS VS GRAVABLES)...");
        
        $agregarItem = function($nombre, $cantidad, $ivaPct) use (&$venta) {
            $var = VarianteProducto::whereHas('producto', fn($q) => $q->where('nombre', $nombre))->first();
            if ($var) {
                $venta = $this->posService->agregarItemVenta($venta, $var, $cantidad, $var->producto->precio_base, 1.0, 0, $ivaPct);
                $tipo = $ivaPct > 0 ? "<fg=red>(G)</>" : "<fg=green>(E)</>";
                $this->line("   🛒 Agregado: {$cantidad} x {$nombre} a $" . $var->producto->precio_base . " " . $tipo);
            }
        };

        // EXENTOS (Cesta básica)
        $agregarItem('Harina P.A.N. Mezcla Maíz 1kg', 3, 0);
        $agregarItem('Cartón de Huevos (30 und)', 1, 0);
        $agregarItem('Queso Blanco Llanero 1kg', 1, 0);
        
        // GRAVABLES (IVA 16% - Procesados, ferretería, licores)
        $agregarItem('Aceite de Maíz Mazeite 1L', 2, 16);
        $agregarItem('Ron Santa Teresa 1796', 1, 16);
        $agregarItem('Cable THW #12', 5, 16);

        $venta->refresh();
        $this->line("   <fg=bright-yellow;options=bold>=> Subtotal (con IVA): $" . number_format($venta->total, 2) . "</>");

        $this->info("\n3️⃣ PROCESANDO PAGOS (APLICANDO IGTF A DIVISAS)...");
        
        $efectivoUSD = MetodoPago::where('nombre', 'Efectivo USD')->first();
        $pagoMovil = MetodoPago::where('nombre', 'Pago Móvil VES')->first();

        // Pago 1: $40 en Efectivo USD (Grava IGTF 3%)
        $montoEfectivo = 40.00;
        $this->posService->registrarPagoConIgtf($venta, $efectivoUSD, $montoEfectivo, 1.0);
        $this->line("   💳 Efectivo USD: $" . $montoEfectivo . " <fg=magenta>(+ IGTF 3% = $1.20)</>");

        // Pago 2: Resto en Pago Móvil VES (No grava IGTF)
        $venta->refresh();
        $restoUSD = $venta->total - $venta->pagos()->sum('monto_en_factura');
        $restoVES = round($restoUSD * $tasaVES, 2);

        $this->posService->registrarPagoConIgtf($venta, $pagoMovil, $restoVES, 1 / $tasaVES);
        $this->line("   📱 Pago Móvil: Bs. " . number_format($restoVES, 2) . " <fg=blue>(Tasa: {$tasaVES})</>");

        $this->info("\n4️⃣ IMPRIMIENDO TICKET FISCAL COMPLETO...");
        $venta = $this->posService->procesarCobroFactura($venta);
        $venta->refresh();

        // Calcular bases manuales para el ticket
        $exento = 0;
        $baseImponible = 0;
        foreach ($venta->items as $item) {
            if ($item->impuesto_monto > 0) {
                $baseImponible += ($item->precio_en_factura * $item->cantidad) - ($item->precio_en_factura * $item->cantidad * ($item->descuento_pct/100));
            } else {
                $exento += ($item->precio_en_factura * $item->cantidad) - ($item->precio_en_factura * $item->cantidad * ($item->descuento_pct/100));
            }
        }

        $this->line("\n");
        $this->line("<fg=black;bg=white>                                                                </>");
        $this->line("<fg=black;bg=white;options=bold>                   TIENDAPOS FISCAL 2026                        </>");
        $this->line("<fg=black;bg=white>                     RIF: J-123456789-0                         </>");
        $this->line("<fg=black;bg=white>                                                                </>");
        $this->line("<fg=black;bg=white>  Factura: {$venta->numero_factura}                                          </>");
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        
        $itemsTable = [];
        foreach ($venta->items as $item) {
            $nombreCorto = substr($item->variante->producto->nombre, 0, 24);
            $tipo = $item->impuesto_monto > 0 ? "(G)" : "(E)";
            $itemsTable[] = [
                $item->cantidad,
                $nombreCorto,
                "$" . number_format($item->precio_en_factura, 2),
                $tipo,
                "$" . number_format($item->total_linea, 2)
            ];
        }
        $this->table(['Cant', 'Descripción', 'P.Unit', 'T', 'Total'], $itemsTable);

        $this->line("<fg=black;bg=white>  (E) Exento | (G) Gravable IVA 16%                             </>");
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        $this->line(sprintf("<fg=black;bg=white>  MONTO EXENTO:                                 $ %7s   </>", number_format($exento, 2)));
        $this->line(sprintf("<fg=black;bg=white>  BASE IMPONIBLE (GRAVABLE):                    $ %7s   </>", number_format($baseImponible, 2)));
        $this->line(sprintf("<fg=black;bg=white>  IVA (16%%):                                    $ %7s   </>", number_format($venta->impuesto_iva, 2)));
        if ($venta->impuesto_igtf > 0) {
            $this->line(sprintf("<fg=black;bg=white>  IGTF PERCIBIDO (3%% sobre Divisa):              $ %7s   </>", number_format($venta->impuesto_igtf, 2)));
        }
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        $this->line(sprintf("<fg=black;bg=white;options=bold>  TOTAL A PAGAR:                                $ %7s   </>", number_format($venta->total, 2)));
        $this->line(sprintf("<fg=black;bg=white;options=bold>  TOTAL EN BS (Tasa %.2f):                  Bs %9s   </>", $tasaVES, number_format($venta->total * $tasaVES, 2)));
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        $this->line("<fg=black;bg=white>  MEDIOS DE PAGO:                                               </>");
        foreach ($venta->pagos as $pago) {
            $moneda = $pago->moneda_pago;
            $monto = number_format($pago->monto_pago, 2);
            $this->line(sprintf("<fg=black;bg=white>  * %-20s %20s %3s   </>", $pago->metodoPago->nombre, $monto, $moneda));
        }
        $this->line("<fg=black;bg=white>                                                                </>");
        $this->line('');
        $this->info("✨ ¡Venta Fiscal (IVA + IGTF) Completada con Éxito!");
    }
}
