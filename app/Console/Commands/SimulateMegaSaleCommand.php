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

class SimulateMegaSaleCommand extends Command
{
    protected $signature = 'pos:mega-sale';
    protected $description = 'Simula una MEGA venta de supermercado multi-moneda con IGTF y pago mixto fraccionado';

    protected $posService;

    public function __construct(PosBusinessRulesService $posService)
    {
        parent::__construct();
        $this->posService = $posService;
    }

    public function handle()
    {
        $this->line('');
        $this->line('<fg=bright-blue;bg=white;options=bold> 🛒 MEGA COMPRA FAMILIAR 2026 - TIENDAPOS NEON TCH </>');
        $this->line('<fg=bright-cyan>===============================================================</>');
        $this->line(' <fg=gray>Simulando carrito masivo con precios actualizados de Venezuela</>');
        $this->line('<fg=bright-cyan>===============================================================</>');
        $this->line('');

        $tienda = Tienda::first();
        if (!$tienda) {
            $this->error('Debe correr el DemoPosSeeder primero.');
            return;
        }

        // Obtener tasa de cambio
        $tasaVES = TasaCambio::where('moneda_destino', 'VES')->where('activa', true)->first()->tasa ?? 592.51;

        // Preparación
        $this->info('1️⃣ CONFIGURANDO SESIÓN...');
        $caja = Caja::first();
        $usuario = User::first() ?? User::factory()->create();
        $cliente = Cliente::first();

        $sesion = SesionCaja::firstOrCreate(
            ['caja_id' => $caja->id, 'estado' => 'abierta'],
            ['user_id' => $usuario->id, 'apertura_en' => now()]
        );

        $venta = Venta::create([
            'numero_factura' => 'MEGA-' . time(),
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

        $this->line("   <fg=green>✓</> Sesión de caja abierta o recuperada (#{$sesion->id})");
        $this->line("   <fg=green>✓</> Ticket borrador creado: {$venta->numero_factura}");

        // Paso 1 - Agregar Items
        $this->info("\n2️⃣ PASANDO PRODUCTOS POR EL LECTOR DE CÓDIGO DE BARRAS...");
        
        // Función auxiliar para buscar y agregar variante
        $agregarItem = function($nombre, $cantidad) use (&$venta) {
            $var = VarianteProducto::whereHas('producto', fn($q) => $q->where('nombre', $nombre))->first();
            if ($var) {
                $venta = $this->posService->agregarItemVenta($venta, $var, $cantidad, $var->producto->precio_base, 1.0, 0, 0);
                $this->line("   🛒 Agregado: {$cantidad} x {$nombre} a $" . $var->producto->precio_base);
            } else {
                $this->warn("   ⚠️ Producto no encontrado: {$nombre}");
            }
        };

        // Carrito de compras realista familiar 2026
        $agregarItem('Harina P.A.N. Mezcla Maíz 1kg', 4);
        $agregarItem('Arroz Blanco Mary 1kg', 3);
        $agregarItem('Queso Blanco Llanero 1kg', 1.5);
        $agregarItem('Cartón de Huevos (30 und)', 1);
        $agregarItem('Café Amanecer 500g', 2);
        $agregarItem('Aceite de Maíz Mazeite 1L', 2);
        $agregarItem('Pasta Mary 500g', 4);
        $agregarItem('Mantequilla Mavesa 500g', 1);
        $agregarItem('Ron Santa Teresa 1796', 1); // El gustico del fin de semana
        $agregarItem('Cable THW #12', 15); // Reparaciones del hogar

        $venta->refresh();
        $this->line("   <fg=bright-yellow;options=bold>=> Subtotal en carrito (sin IGTF): $" . number_format($venta->total, 2) . "</>");

        // Paso 2 - Pago Complejo Fraccionado
        $this->info("\n3️⃣ PROCESANDO PAGOS MULTIMONEDA COMPLEJOS...");
        
        $efectivoUSD = MetodoPago::where('nombre', 'Efectivo USD')->first();
        $zelle = MetodoPago::where('nombre', 'Zelle')->first();
        $pagoMovil = MetodoPago::where('nombre', 'Pago Móvil VES')->first();

        // Cliente paga: $50 en Efectivo USD, $20 en Zelle, y el resto en Pago Móvil
        
        // Pago 1: $50 Efectivo USD (Grava IGTF 3% sobre el pago = $1.50 extra)
        $montoEfectivo = 50.00;
        $this->posService->registrarPagoConIgtf($venta, $efectivoUSD, $montoEfectivo, 1.0);
        $this->line("   💳 Pago 1: $" . $montoEfectivo . " en " . $efectivoUSD->nombre . " <fg=red>(+ IGTF 3%)</>");

        // Pago 2: $20 Zelle (Grava IGTF 3% = $0.60 extra)
        $montoZelle = 20.00;
        $this->posService->registrarPagoConIgtf($venta, $zelle, $montoZelle, 1.0);
        $this->line("   💳 Pago 2: $" . $montoZelle . " en " . $zelle->nombre . " <fg=red>(+ IGTF 3%)</>");

        // Pago 3: Resto en Pago Móvil (No grava IGTF)
        $venta->refresh();
        $restoUSD = $venta->total - $venta->pagos()->sum('monto_en_factura');
        $restoVES = round($restoUSD * $tasaVES, 2);

        $this->posService->registrarPagoConIgtf($venta, $pagoMovil, $restoVES, 1 / $tasaVES);
        $this->line("   📱 Pago 3: Bs. " . number_format($restoVES, 2) . " en " . $pagoMovil->nombre . " <fg=blue>(Tasa BCV: {$tasaVES})</>");

        // Paso 3 - Cerrar Venta
        $this->info("\n4️⃣ IMPRIMIENDO TICKET FISCAL...");
        $venta = $this->posService->procesarCobroFactura($venta);
        $venta->refresh();

        // SALIDA VISUAL: RECIBO ÉPICO
        $this->line("\n");
        $this->line("<fg=black;bg=white>                                                                </>");
        $this->line("<fg=black;bg=white;options=bold>                   TIENDAPOS MEGA STORE 2026                    </>");
        $this->line("<fg=black;bg=white>                     RIF: J-123456789-0                         </>");
        $this->line("<fg=black;bg=white>                                                                </>");
        $this->line("<fg=black;bg=white>  Factura: {$venta->numero_factura}                                          </>");
        $this->line("<fg=black;bg=white>  Fecha: " . now()->format('d/m/Y H:i') . "                                    </>");
        $this->line("<fg=black;bg=white>  Cajero: {$usuario->name}                                             </>");
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        
        $itemsTable = [];
        foreach ($venta->items as $item) {
            $nombreCorto = substr($item->variante->producto->nombre, 0, 26);
            $itemsTable[] = [
                $item->cantidad,
                $nombreCorto,
                "$" . number_format($item->precio_en_factura, 2),
                "$" . number_format($item->total_linea, 2)
            ];
        }
        $this->table(['Cant', 'Descripción', 'P.Unit USD', 'Total USD'], $itemsTable);

        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        $this->line(sprintf("<fg=black;bg=white>  SUBTOTAL:                                     $ %7s   </>", number_format($venta->subtotal, 2)));
        $this->line(sprintf("<fg=black;bg=white>  BASE IMPONIBLE EXENTA:                        $ %7s   </>", number_format($venta->subtotal, 2)));
        $this->line(sprintf("<fg=black;bg=white>  IVA (16%%):                                    $ %7s   </>", "0.00"));
        if ($venta->impuesto_igtf > 0) {
            $this->line(sprintf("<fg=black;bg=white>  IGTF PERCIBIDO (3%% sobre divisa):              $ %7s   </>", number_format($venta->impuesto_igtf, 2)));
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
        $this->line("<fg=black;bg=white;options=bold>                ¡GRACIAS POR SU COMPRA!                         </>");
        $this->line("<fg=black;bg=white>                                                                </>");
        $this->line('');
        $this->info("✨ ¡MEGA Simulación Completada con Éxito!");
    }
}
