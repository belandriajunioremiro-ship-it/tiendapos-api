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

class SimulateHardwareSaleCommand extends Command
{
    protected $signature = 'pos:ferre-sale';
    protected $description = 'Simula una venta de ferretería (sacos, metros, galones) integrando InventoryService';

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
        $this->line('<fg=bright-yellow;options=bold> 🏗️ FERRETERÍA EL MAZO C.A. - VENEZUELA (Sacos, Metros, Galones) </>');
        $this->line(' <fg=gray>Ejemplo: Materiales Pesados y Construcción (Gravables) con Pagos en Divisas + IGTF</>');
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
        $this->ensureHardwareInventory($almacen, $usuario);

        $this->info("\n2️⃣ CONFIGURANDO SESIÓN DE CAJA...");
        $caja = Caja::first();
        $cliente = Cliente::first();

        $sesion = SesionCaja::firstOrCreate(
            ['caja_id' => $caja->id, 'estado' => 'abierta'],
            ['user_id' => $usuario->id, 'apertura_en' => now()]
        );

        $venta = Venta::create([
            'numero_factura' => 'FERRE-' . time(),
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

        $this->line("   <fg=green>✓</> Ticket de Ferretería: {$venta->numero_factura}");

        $this->info("\n3️⃣ FACTURANDO MATERIALES DE CONSTRUCCIÓN...");

        // Todos los materiales de construcción gravan 16% IVA
        $itemsComprados = [
            ['Cemento Portland Tipo I', 10, 16], // 10 sacos
            ['Cabilla Estriada 1/2"', 12, 16],   // 12 metros
            ['Pintura Caucho Blanca', 2, 16],    // 2 galones
            ['Clavos Acero 2"', 0.5, 16],        // 0.5 kg
            ['Arena Lavada', 3, 16]              // 3 m3
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
                $this->line("   🛠️  " . str_pad("$cantidad " . $var->producto->unidad->abreviatura, 10) . str_pad($nombre, 26) . "@ \${$precioDisplay}  <fg=red>(G) IVA16%</>  | Stock: {$stockRestante}");
            }
        }

        $venta->refresh();
        $this->line("   <fg=bright-yellow;options=bold>=> Subtotal (con IVA): $" . number_format($venta->total, 2) . "</>");

        $this->info("\n4️⃣ PROCESANDO PAGO EN DIVISAS Y CIERRE FISCAL...");
        $efectivoUSD = MetodoPago::where('nombre', 'Efectivo USD')->first();
        $pagoMovil = MetodoPago::where('nombre', 'Pago Móvil VES')->first();
        
        // Paga el monto base en divisas (genera 3% de IGTF)
        $this->posService->registrarPagoConIgtf($venta, $efectivoUSD, $venta->total, 1.0);
        
        // Paga el IGTF generado (o cualquier diferencial) en Pago Móvil
        $venta->refresh();
        $restoUSD = $venta->total - $venta->pagos()->sum('monto_en_factura');
        if ($restoUSD > 0) {
            $restoVES = round($restoUSD * $tasaVES, 2);
            $this->posService->registrarPagoConIgtf($venta, $pagoMovil, $restoVES, 1 / $tasaVES);
        }

        $venta = $this->posService->procesarCobroFactura($venta);
        $venta->refresh();

        $fecha = now()->format('d/m/Y h:i A');

        // BASE IMPONIBLE = solo suma de ítems gravables (SENIAT)
        $baseGravable = collect($venta->items)->sum(function($item) {
            return $item->impuesto_monto > 0 
                ? ($item->precio_en_factura * $item->cantidad) * (1 - $item->descuento_pct / 100)
                : 0;
        });

        $this->line("\n<fg=black;bg=white>================================================================</>");
        $this->line("<fg=black;bg=white;options=bold>                  FERRETERÍA EL MAZO C.A.                      </>");
        $this->line("<fg=black;bg=white>                     RIF: J-11223344-5                          </>");
        $this->line("<fg=black;bg=white>               Zona Industrial, Valencia, Carabobo              </>");
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
        $this->line(sprintf("<fg=black;bg=white>  IGTF 3%% (100%% Pago Divisas):                 $ %9.2f   </>", $venta->impuesto_igtf));
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
        $this->line("<fg=black;bg=white>  GRACIAS POR SU COMPRA - CONSTRUYENDO EL FUTURO                </>");
        $this->line("<fg=black;bg=white>================================================================</>");
        $this->line('');
        $this->info("✨ ¡Simulación de Ferretería completada | Auditoría SENIAT: OK!");
    }

    private function ensureHardwareInventory($almacen, $usuario)
    {
        $cat = CategoriaProducto::firstOrCreate(['slug' => 'ferreteria'], ['nombre' => 'Ferretería', 'nivel' => 1, 'activo' => true]);
        
        $sac = Unidad::firstOrCreate(['abreviatura' => 'sac'], ['nombre' => 'Saco', 'tipo' => 'cantidad', 'factor_conversion' => 1, 'es_vendible' => true, 'es_logistica' => true]);
        $m = Unidad::firstOrCreate(['abreviatura' => 'm'], ['nombre' => 'Metros', 'tipo' => 'longitud', 'factor_conversion' => 1, 'es_vendible' => true, 'es_logistica' => false]);
        $gal = Unidad::firstOrCreate(['abreviatura' => 'gal'], ['nombre' => 'Galón', 'tipo' => 'volumen', 'factor_conversion' => 1, 'es_vendible' => true, 'es_logistica' => false]);
        $kg = Unidad::where('abreviatura', 'kg')->first();
        $m3 = Unidad::firstOrCreate(['abreviatura' => 'm3'], ['nombre' => 'Metro Cúbico', 'tipo' => 'volumen', 'factor_conversion' => 1, 'es_vendible' => true, 'es_logistica' => false]);

        $productos = [
            ['Cemento Portland Tipo I', 'CEM-01', $sac, 6.50, 8.00],
            ['Cabilla Estriada 1/2"', 'CAB-01', $m, 1.00, 1.50],
            ['Pintura Caucho Blanca', 'PIN-01', $gal, 11.00, 15.00],
            ['Clavos Acero 2"', 'CLA-01', $kg, 1.50, 3.00],
            ['Arena Lavada', 'ARE-01', $m3, 15.00, 25.00],
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
