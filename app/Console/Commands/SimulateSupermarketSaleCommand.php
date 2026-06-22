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

class SimulateSupermarketSaleCommand extends Command
{
    protected $signature = 'pos:super-sale';
    protected $description = 'Simula una venta de supermercado (kilos, gramos, unds) integrando InventoryService y Costos en Venezuela';

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
        $this->line('<fg=bright-green;options=bold> 🛒 SUPERMERCADOS EL CAURIMARE - VENEZUELA (Kilos, Gramos y Unidades) </>');
        $this->line(' <fg=gray>Ejemplo: Productos de Cesta Básica (Exentos) vs Procesados (Gravables)</>');
        $this->line('');

        // Forzar a que los Jobs (como RecalcularCostoPromedio) se ejecuten síncronamente
        config(['queue.default' => 'sync']);

        $tienda = Tienda::first();
        if (!$tienda) {
            $this->error('Debe correr el DemoPosSeeder primero.');
            return;
        }

        $tasaVES = TasaCambio::where('moneda_destino', 'VES')->where('activa', true)->first()->tasa ?? 60.50; // Ajustado a valor referencial futuro
        $almacen = Almacen::first();
        $usuario = User::first() ?? User::factory()->create();

        $this->info('1️⃣ PREPARANDO INVENTARIO Y COSTOS (InventoryService)...');
        $this->ensureSupermarketInventory($almacen, $usuario);

        $this->info("\n2️⃣ CONFIGURANDO SESIÓN DE CAJA...");
        $caja = Caja::first();
        $cliente = Cliente::first();

        $sesion = SesionCaja::firstOrCreate(
            ['caja_id' => $caja->id, 'estado' => 'abierta'],
            ['user_id' => $usuario->id, 'apertura_en' => now()]
        );

        $venta = Venta::create([
            'numero_factura' => 'SUPER-' . time(),
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

        $this->line("   <fg=green>✓</> Ticket de Supermercado: {$venta->numero_factura}");

        $this->info("\n3️⃣ ESCANEANDO PRODUCTOS (BALANZA Y LECTURA)...");

        $itemsComprados = [
            // nombre, cantidad, ivaPct
            ['Pollo Entero Fresco', 2.350, 0],   // 2.35 kg (Exento)
            ['Queso Blanco Duro', 0.850, 0],     // 850 gramos (Exento)
            ['Harina PAN Maíz Blanco', 4, 0],    // 4 unidades (Exento)
            ['Carne Molida Primera', 1.150, 0],  // 1.15 kg (Exento)
            ['Bolsa Reutilizable Grande', 1, 16] // 1 unidad (Gravable)
        ];

        foreach ($itemsComprados as $itemData) {
            $nombre = $itemData[0];
            $cantidad = $itemData[1];
            $ivaPct = $itemData[2];

            $var = VarianteProducto::whereHas('producto', fn($q) => $q->where('nombre', $nombre))->first();
            if ($var) {
                $var->producto->refresh();
                $precioDisplay = number_format((float) $var->producto->precio_base, 2, '.', '');
                // 1. Agregar a la factura fiscal
                $venta = $this->posService->agregarItemVenta($venta, $var, $cantidad, $var->producto->precio_base, 1.0, 0, $ivaPct);
                
                // 2. Descontar del inventario real (InventoryService)
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
                $tipo = $ivaPct > 0 ? "<fg=red>(G)</> <fg=gray>IVA 16%</>" : "<fg=green>(E)</> <fg=gray>Exento</>";
                $this->line("   🛒  " . str_pad("$cantidad " . $var->producto->unidad->abreviatura, 10) . str_pad($nombre, 26) . "@ \${$precioDisplay}  {$tipo}  | Stock: {$stockRestante}");
            }
        }

        $venta->refresh();
        $this->line("   <fg=bright-yellow;options=bold>=> Subtotal (con IVA): $" . number_format($venta->total, 2) . "</>");

        $this->info("\n4️⃣ PROCESANDO PAGO Y FACTURA...");
        $pagoMovil = MetodoPago::where('nombre', 'Pago Móvil VES')->first();
        $montoVES = round($venta->total * $tasaVES, 2);
        
        $this->posService->registrarPagoConIgtf($venta, $pagoMovil, $montoVES, 1 / $tasaVES);
        $venta = $this->posService->procesarCobroFactura($venta);

        $fecha = now()->format('d/m/Y h:i A');

        // Calcular bases correctamente para el ticket SENIAT
        $baseGravable = 0;
        $baseExenta   = 0;
        foreach ($venta->items as $item) {
            $neto = ($item->precio_en_factura * $item->cantidad) * (1 - $item->descuento_pct / 100);
            if ($item->impuesto_monto > 0) {
                $baseGravable += $neto;
            } else {
                $baseExenta   += $neto;
            }
        }

        $this->line("\n<fg=black;bg=white>================================================================</>");
        $this->line("<fg=black;bg=white;options=bold>               SUPERMERCADOS EL CAURIMARE C.A.                  </>");
        $this->line("<fg=black;bg=white>                     RIF: J-00123456-7                          </>");
        $this->line("<fg=black;bg=white>               Av. Principal, Caracas, Miranda                  </>");
        $this->line("<fg=black;bg=white>================================================================</>");
        $this->line("<fg=black;bg=white>  Factura: {$venta->numero_factura}                                         </>");
        $this->line("<fg=black;bg=white>  Fecha:   {$fecha}                                  </>");
        $this->line("<fg=black;bg=white>  Cajero:  " . str_pad($usuario->name ?? 'Admin', 45) . "  </>");
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        $this->line("<fg=black;bg=white>  CANT    DESCRIPCIÓN              P.UNIT  (T)    TOTAL USD   </>");
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        
        foreach ($venta->items as $item) {
            $nombreCorto = substr($item->variante->producto->nombre, 0, 22);
            $tipo = $item->impuesto_monto > 0 ? "(G)" : "(E)";
            $this->line(sprintf("<fg=black;bg=white>  %-6s  %-22s %6.2f %3s   %8.2f   </>", 
                $item->cantidad, $nombreCorto, $item->precio_en_factura, $tipo, $item->total_linea));
        }
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        $this->line(sprintf("<fg=black;bg=white>  EXENTO (Cesta Básica):                      $ %9.2f   </>", $baseExenta));
        $this->line(sprintf("<fg=black;bg=white>  BASE IMPONIBLE (Solo Gravables):             $ %9.2f   </>", $baseGravable));
        $this->line(sprintf("<fg=black;bg=white>  IVA 16%%:                                     $ %9.2f   </>", $venta->impuesto_iva));
        $this->line("<fg=black;bg=white>================================================================</>");
        $this->line(sprintf("<fg=black;bg=white;options=bold>  TOTAL A PAGAR USD:                           $ %9.2f   </>", $venta->total));
        $this->line(sprintf("<fg=black;bg=white;options=bold>  TOTAL A PAGAR VES (Tasa %s):         Bs %12.2f   </>", 
            number_format($tasaVES, 2), $venta->total * $tasaVES));
        $this->line("<fg=black;bg=white>----------------------------------------------------------------</>");
        $this->line("<fg=black;bg=white>  MEDIO DE PAGO:                                                </>");
        foreach ($venta->pagos as $pago) {
            $this->line(sprintf("<fg=black;bg=white>  * %-22s  %3s  %12.2f                  </>", 
                $pago->metodoPago->nombre, $pago->moneda_pago, $pago->monto_pago));
        }
        $this->line("<fg=black;bg=white>                                                                </>");
        $this->line("<fg=black;bg=white>  ¡GRACIAS POR SU COMPRA! - VUELVA PRONTO                       </>");
        $this->line("<fg=black;bg=white>================================================================</>");
        $this->line('');
        $this->info("✨ ¡Simulación de Supermercado completada | Auditoría SENIAT: OK!");
    }

    private function ensureSupermarketInventory($almacen, $usuario)
    {
        $cat = CategoriaProducto::firstOrCreate(['slug' => 'supermercado'], ['nombre' => 'Supermercado', 'nivel' => 1, 'activo' => true]);
        
        $kg = Unidad::where('abreviatura', 'kg')->first();
        $und = Unidad::where('abreviatura', 'und')->first();

        $productos = [
            ['Pollo Entero Fresco', 'POLLO-01', $kg, 2.50, 3.50],
            ['Queso Blanco Duro', 'QUESO-01', $kg, 3.00, 5.00],
            ['Harina PAN Maíz Blanco', 'HPAN-01', $und, 0.90, 1.20],
            ['Carne Molida Primera', 'CARNE-01', $kg, 4.50, 6.50],
            ['Bolsa Reutilizable Grande', 'BOLSA-01', $und, 0.20, 0.50],
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

            // Inyectar 100 de stock para poder vender usando el InventoryService!
            $inv = Inventario::where('variante_id', $var->id)->where('almacen_id', $almacen->id)->first();
            if (!$inv || $inv->cantidad_disponible < 10) {
                $dtoRecepcion = new RecepcionItemData(
                    varianteId: $var->id,
                    almacenId: $almacen->id,
                    cantidad: 100, // 100 kilos o unidades
                    unidadOrigenId: $p[2]->id,
                    costoUnitario: $p[3], // Costo de compra
                    userId: $usuario->id,
                    referenciaTipo: 'ajustes_inventario',
                    referenciaId: 1
                );
                $this->inventoryService->recibir($dtoRecepcion);
            }
        }
    }
}
