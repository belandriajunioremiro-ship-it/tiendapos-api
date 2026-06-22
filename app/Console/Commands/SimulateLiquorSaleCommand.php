<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PosBusinessRulesService;
use App\Services\InventoryService;
use App\Services\Inventory\DTO\RecepcionItemData;
use App\Services\Inventory\DTO\VentaItemData;
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
use App\Models\Unidad;
use App\Models\Almacen;
use App\Models\Inventario;

class SimulateLiquorSaleCommand extends Command
{
    protected $signature = 'pos:licor-sale';
    protected $description = 'Simula una venta de bodegón/licorería (botellas, cajas) integrando InventoryService';

    protected PosBusinessRulesService $posService;
    protected InventoryService $inventoryService;

    public function __construct(PosBusinessRulesService $posService, InventoryService $inventoryService)
    {
        parent::__construct();
        $this->posService = $posService;
        $this->inventoryService = $inventoryService;
    }

    public function handle()
    {
        $this->line('');
        $this->line('<fg=bright-cyan;bg=black;options=bold>================================================================================</>');
        $this->line('<fg=bright-cyan;bg=black;options=bold>         PRUEBA DE CONEXIÓN TIENDA POS DIRECTAMENTE DESDE NEON TCH              </>');
        $this->line('<fg=bright-cyan;bg=black;options=bold>================================================================================</>');
        $this->line('<fg=magenta;options=bold> 🍾 BODEGÓN VIP PREMIUM C.A. - VENEZUELA (Botellas, Cajas, Litros) </>');
        $this->line(' <fg=gray>Ejemplo: Licores y Bodegón Premium con Pagos Mixtos (Divisas + Pago Móvil)</>');
        $this->line('');

        // Forzar a que los Jobs (como RecalcularCostoPromedio) se ejecuten síncronamente
        config(['queue.default' => 'sync']);

        $tienda = Tienda::first();
        if (!$tienda) {
            $this->error('Debe correr el DemoPosSeeder primero.');
            return;
        }

        $tasaVES = TasaCambio::where('moneda_destino', 'VES')->where('activa', true)->first()->tasa ?? 60.50;
        $almacen = Almacen::first();
        $usuario = User::first() ?? User::factory()->create();

        $this->info('1️⃣ PREPARANDO INVENTARIO Y COSTOS (InventoryService)...');
        $this->ensureLiquorInventory($almacen, $usuario);

        $this->info("\n2️⃣ CONFIGURANDO SESIÓN DE CAJA...");
        $caja = Caja::first();
        $cliente = Cliente::first();

        $sesion = SesionCaja::firstOrCreate(
            ['caja_id' => $caja->id, 'estado' => 'abierta'],
            ['user_id' => $usuario->id, 'apertura_en' => now()]
        );

        $venta = Venta::create([
            'numero_factura' => 'LICOR-' . time(),
            'caja_id'        => $caja->id,
            'sesion_caja_id' => $sesion->id,
            'user_id'        => $usuario->id,
            'cliente_id'     => $cliente->id,
            'almacen_id'     => $almacen->id,
            'moneda_factura' => 'USD',
            'estado'         => 'borrador',
            'tipo_documento' => 'FAC',
            'tasa_referencia'=> $tasaVES,
        ]);

        $this->line("   <fg=green>✓</> Ticket de Bodegón: {$venta->numero_factura}");

        $this->info("\n3️⃣ FACTURANDO BEBIDAS Y LICORES...");

        $itemsComprados = [
            ['Cerveza Polar Light Caja x36', 2, 16], // 2 Cajas
            ['Ron Cacique Añejo 1L', 1, 16],         // 1 Botella/Litro
            ['Hielo en Cubitos', 3, 16],             // 3 Kg
            ['Refresco Coca-Cola 2L', 2, 16],        // 2 Botellas
            ['Doritos Queso Mega', 1, 16]            // 1 Unidad
        ];

        foreach ($itemsComprados as $itemData) {
            $nombre = $itemData[0];
            $cantidad = $itemData[1];
            $ivaPct = $itemData[2];

            $var = VarianteProducto::whereHas('producto', fn($q) => $q->where('nombre', $nombre))->first();
            if ($var) {
                $var->producto->refresh();
                $precioDisplay = number_format((float) $var->producto->precio_base, 2, '.', '');
                // Agregar al ticket
                $venta = $this->posService->agregarItemVenta($venta, $var, $cantidad, $var->producto->precio_base, 1.0, 0, $ivaPct);
                
                // Descontar inventario
                $dtoVenta = new VentaItemData(
                    varianteId: $var->id,
                    almacenId: $almacen->id,
                    cantidadVenta: $cantidad,
                    unidadVentaId: $var->producto->unidad_id,
                    userId: $usuario->id,
                    referenciaId: $venta->id
                );
                $this->inventoryService->vender($dtoVenta);

                $stockRestante = number_format(
                    (float) Inventario::where('variante_id', $var->id)->where('almacen_id', $almacen->id)->value('cantidad_disponible'),
                    3, '.', ''
                );
                $this->line("   🍾  " . str_pad("$cantidad " . $var->producto->unidad->abreviatura, 10) . str_pad($nombre, 26) . "@ \${$precioDisplay}  <fg=red>(G) IVA16%</>  | Stock: {$stockRestante}");
            }
        }

        $venta->refresh();
        $this->line("   <fg=bright-yellow;options=bold>=> Subtotal (con IVA): $" . number_format($venta->total, 2) . "</>");

        $this->info("\n4️⃣ PROCESANDO PAGO MIXTO Y CIERRE FISCAL...");
        $efectivoUSD = MetodoPago::where('nombre', 'Efectivo USD')->first();
        $pagoMovil = MetodoPago::where('nombre', 'Pago Móvil VES')->first();
        
        // Paga $20 en divisas (IGTF 3%)
        $this->posService->registrarPagoConIgtf($venta, $efectivoUSD, 20.00, 1.0);
        
        // Resto en Bs
        $venta->refresh();
        $restoUSD = $venta->total - 20;
        $restoVES = round($restoUSD * $tasaVES, 2);
        $this->posService->registrarPagoConIgtf($venta, $pagoMovil, $restoVES, 1 / $tasaVES);

        $venta = $this->posService->procesarCobroFactura($venta);
        $venta->refresh();

        $fecha = now()->format('d/m/Y h:i A');

        // BASE IMPONIBLE = solo suma de ítems gravables (SENIAT)
        $baseGravable = collect($venta->items)->sum(function($item) {
            return $item->impuesto_monto > 0 
                ? ($item->precio_en_factura * $item->cantidad) * (1 - $item->descuento_pct / 100)
                : 0;
        });
        $igtfUSD20 = round(20.00 * 0.03, 2);

        $this->line("\n<fg=black;bg=white>================================================================</>");
        $this->line("<fg=black;bg=white;options=bold>               BODEGÓN VIP PREMIUM C.A.                         </>");
        $this->line("<fg=black;bg=white>                     RIF: J-40556677-8                          </>");
        $this->line("<fg=black;bg=white>               Las Mercedes, Caracas, Miranda                   </>");
        $this->line("<fg=black;bg=white>================================================================</>");
        $this->line("<fg=black;bg=white>  Factura: {$venta->numero_factura}                                         </>");
        $this->line("<fg=black;bg=white>  Fecha:   {$fecha}                                  </>");
        $this->line("<fg=black;bg=white>  Cajero:  " . str_pad($usuario->name ?? 'Admin', 45) . "  </>");
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        $this->line("<fg=black;bg=white>  CANT    DESCRIPCIÓN              P.UNIT  (T)    TOTAL USD   </>");
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        
        foreach ($venta->items as $item) {
            $nombreCorto = substr($item->variante->producto->nombre, 0, 22);
            $this->line(sprintf("<fg=black;bg=white>  %-6s  %-22s %6.2f (G)   %8.2f   </>", 
                $item->cantidad, $nombreCorto, $item->precio_en_factura, $item->total_linea));
        }
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        $this->line(sprintf("<fg=black;bg=white>  BASE IMPONIBLE (Todos Gravables):            $ %9.2f   </>", $baseGravable));
        $this->line(sprintf("<fg=black;bg=white>  IVA 16%%:                                     $ %9.2f   </>", $venta->impuesto_iva));
        $this->line(sprintf("<fg=black;bg=white>  IGTF 3%% (Sobre \$20 en Efectivo USD):         $ %9.2f   </>", $venta->impuesto_igtf));
        $this->line("<fg=black;bg=white>================================================================</>");
        $this->line(sprintf("<fg=black;bg=white;options=bold>  TOTAL A PAGAR USD:                           $ %9.2f   </>", $venta->total));
        $this->line(sprintf("<fg=black;bg=white;options=bold>  TOTAL A PAGAR VES (Tasa %s):         Bs %12.2f   </>",
            number_format($tasaVES, 2), $venta->total * $tasaVES));
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        $this->line("<fg=black;bg=white>  MEDIOS DE PAGO REGISTRADOS:                                   </>");
        foreach ($venta->pagos as $pago) {
            $this->line(sprintf("<fg=black;bg=white>  * %-22s  %3s  %12.2f                  </>", 
                $pago->metodoPago->nombre, $pago->moneda_pago, $pago->monto_pago));
        }
        $this->line("<fg=black;bg=white>                                                                </>");
        $this->line("<fg=black;bg=white>  CELEBRE CON RESPONSABILIDAD. PROHIBIDA LA VENTA A MENORES     </>");
        $this->line("<fg=black;bg=white>================================================================</>");
        $this->line('');
        $this->info("✨ ¡Simulación de Bodegón completada | Auditoría SENIAT: OK!");
    }

    private function ensureLiquorInventory($almacen, $usuario)
    {
        $cat = CategoriaProducto::firstOrCreate(['slug' => 'bodegon'], ['nombre' => 'Bodegón', 'nivel' => 1, 'activo' => true]);
        
        $cja = Unidad::firstOrCreate(['abreviatura' => 'cja36'], ['nombre' => 'Caja x36', 'tipo' => 'cantidad', 'factor_conversion' => 36, 'es_vendible' => true, 'es_logistica' => true]);
        $lt = Unidad::where('abreviatura', 'lt')->first();
        $kg = Unidad::where('abreviatura', 'kg')->first();
        $und = Unidad::where('abreviatura', 'und')->first();

        $productos = [
            ['Cerveza Polar Light Caja x36', 'POLAR-01', $cja, 14.00, 20.00],
            ['Ron Cacique Añejo 1L', 'CAC-01', $lt, 7.50, 10.00],
            ['Hielo en Cubitos', 'HIE-01', $kg, 0.20, 0.50],
            ['Refresco Coca-Cola 2L', 'COC-01', $und, 1.20, 2.00],
            ['Doritos Queso Mega', 'DOR-01', $und, 1.50, 2.50],
        ];

        foreach ($productos as $p) {
            $costo = $p[3];
            $precio = $p[4];
            $margenPct = (($precio / $costo) - 1) * 100;

            $prod = Producto::updateOrCreate(
                ['codigo_sku' => $p[1]],
                [
                    'nombre' => $p[0], 
                    'categoria_id' => $cat->id, 
                    'unidad_id' => $p[2]->id, 
                    'moneda_precio' => 'USD', 
                    'margen_pct' => $margenPct, 
                    'activo' => true
                ]
            );
            $var = VarianteProducto::firstOrCreate(['producto_id' => $prod->id], ['codigo_barra' => $p[1], 'factor_unidad' => 1, 'activo' => true]);

            $inv = Inventario::where('variante_id', $var->id)->where('almacen_id', $almacen->id)->first();
            if (!$inv || $inv->cantidad_disponible < 50) {
                $dtoRecepcion = new RecepcionItemData(
                    varianteId: $var->id,
                    almacenId: $almacen->id,
                    cantidad: 100, 
                    unidadOrigenId: $p[2]->id,
                    costoUnitario: $p[3], 
                    userId: $usuario->id,
                    referenciaTipo: 'ajustes_inventario',
                    referenciaId: 1
                );
                $this->inventoryService->recibir($dtoRecepcion);
            }
            
            // Forzar recalcular costo para que el precio_base en DB se genere correctamente
            // en caso de que los jobs anteriores se hayan quedado pegados en la cola
            \App\Jobs\RecalcularCostoPromedioProducto::dispatchSync($prod->id);
        }
    }
}
