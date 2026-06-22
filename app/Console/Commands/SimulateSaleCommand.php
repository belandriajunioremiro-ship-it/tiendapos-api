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

class SimulateSaleCommand extends Command
{
    protected $signature = 'pos:simulate-sale';
    protected $description = 'Simula una venta multi-moneda con IGTF usando el PosBusinessRulesService';

    protected $posService;

    public function __construct(PosBusinessRulesService $posService)
    {
        parent::__construct();
        $this->posService = $posService;
    }

    public function handle()
    {
        $this->line('');
        $this->line('<fg=bright-cyan;options=bold>   PRUEBA DE CONEXIÓN TIENDA POS DIRECTAMENTE DESDE NEON TCH   </>');
        $this->line('');

        $tienda = Tienda::first();
        if (!$tienda) {
            $this->error('Debe correr el DemoPosSeeder primero.');
            return;
        }

        // Obtener tasa de cambio
        $tasaVES = TasaCambio::where('moneda_destino', 'VES')->where('activa', true)->first()->tasa ?? 592.51;

        // Preparación
        $this->info('1️⃣ PREPARACIÓN...');
        $caja = Caja::first();
        $usuario = User::first() ?? User::factory()->create();
        $cliente = Cliente::first();

        $sesion = SesionCaja::firstOrCreate(
            ['caja_id' => $caja->id, 'estado' => 'abierta'],
            ['user_id' => $usuario->id, 'apertura_en' => now()]
        );

        $venta = Venta::create([
            'numero_factura' => 'SIM-' . time(),
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

        $this->line("   ✓ Sesión de caja abierta (#{$sesion->id})");
        $this->line("   ✓ Venta borrador creada en USD");

        // Paso 1 - Agregar Items
        $this->info("\n2️⃣ AGREGANDO ITEMS (PosBusinessRulesService->agregarItemVenta)...");
        
        $var1 = VarianteProducto::whereHas('producto', fn($q) => $q->where('nombre', 'Amoxicilina 500mg'))->first();
        $var2 = VarianteProducto::whereHas('producto', fn($q) => $q->where('nombre', 'Cable THW #12'))->first();
        $var3 = VarianteProducto::whereHas('producto', fn($q) => $q->where('nombre', 'Ron Santa Teresa 1796'))->first();

        // 2 Cajas de Amoxicilina ($5 c/u)
        $venta = $this->posService->agregarItemVenta($venta, $var1, 2, $var1->producto->precio_base, 1.0, 0, 0);
        $this->line("   + 2 x {$var1->producto->nombre} ($" . $var1->producto->precio_base . ")");
        
        // 5 Metros de cable ($1.20 c/u)
        $venta = $this->posService->agregarItemVenta($venta, $var2, 5, $var2->producto->precio_base, 1.0, 0, 0);
        $this->line("   + 5 x {$var2->producto->nombre} ($" . $var2->producto->precio_base . ")");
        
        // 1 Ron ($35 c/u)
        $venta = $this->posService->agregarItemVenta($venta, $var3, 1, $var3->producto->precio_base, 1.0, 0, 0);
        $this->line("   + 1 x {$var3->producto->nombre} ($" . $var3->producto->precio_base . ")");

        $venta->refresh();
        $this->line("   <fg=bright-cyan>Subtotal parcial (sin IGTF): $" . $venta->total . "</>");

        // Paso 2 - Pago Mixto
        $this->info("\n3️⃣ PAGOS MULTIMONEDA E IGTF (PosBusinessRulesService->registrarPagoConIgtf)...");
        
        $efectivoUSD = MetodoPago::where('nombre', 'Efectivo USD')->first();
        $pagoMovil = MetodoPago::where('nombre', 'Pago Móvil VES')->first();

        $totalPagar = $venta->total; // Ej: $51.00
        
        // Pago 1: $20 en Efectivo USD (Debe calcular IGTF 3% = $0.60)
        $montoUSD = 20.00;
        $this->posService->registrarPagoConIgtf($venta, $efectivoUSD, $montoUSD, 1.0);
        $this->line("   + Pago 1: $" . $montoUSD . " USD en Efectivo (Grava IGTF 3%)");

        // Pago 2: Resto en Pago Móvil VES (Sin IGTF)
        $venta->refresh();
        $restoUSD = $venta->total - $venta->pagos()->sum('monto_en_factura');
        $restoVES = round($restoUSD * $tasaVES, 2);

        $this->posService->registrarPagoConIgtf($venta, $pagoMovil, $restoVES, 1 / $tasaVES);
        $this->line("   + Pago 2: Bs. " . $restoVES . " en Pago Móvil VES (Tasa: {$tasaVES})");

        // Paso 3 - Cerrar Venta
        $this->info("\n4️⃣ CERRANDO VENTA (PosBusinessRulesService->procesarCobroFactura)...");
        $venta = $this->posService->procesarCobroFactura($venta);
        $venta->refresh();

        // SALIDA VISUAL: RECIBO
        $this->line("\n<fg=bright-green;options=bold>=============================================</>");
        $this->line("<fg=bright-green;options=bold>           RECIBO DE VENTA #{$venta->numero_factura}           </>");
        $this->line("<fg=bright-green;options=bold>=============================================</>");
        $this->line(" Estado: <fg=bright-cyan;options=bold>{$venta->estado}</> | Moneda: {$venta->moneda_factura} | Tasa: {$venta->tasa_referencia}");
        $this->line("---------------------------------------------");

        $itemsTable = [];
        foreach ($venta->items as $item) {
            $itemsTable[] = [
                $item->cantidad,
                $item->variante->producto->nombre,
                "$" . number_format($item->precio_en_factura, 2),
                "$" . number_format($item->total_linea, 2)
            ];
        }
        $this->table(['Cant.', 'Producto', 'P.Unit', 'Total'], $itemsTable);

        $this->line("---------------------------------------------");
        $this->line(sprintf(" <fg=white>Subtotal:          $%s</>", number_format($venta->subtotal, 2)));
        $this->line(sprintf(" <fg=white>Impuesto IVA:      $%s</>", number_format($venta->impuesto_iva, 2)));
        $this->line(sprintf(" <fg=bright-magenta>Impuesto IGTF (3%%): $%s</>", number_format($venta->impuesto_igtf, 2)));
        $this->line(sprintf(" <fg=bright-cyan;options=bold>TOTAL A PAGAR:     $%s</>", number_format($venta->total, 2)));
        $this->line("---------------------------------------------");
        $this->line(" DETALLE DE PAGOS:");
        foreach ($venta->pagos as $pago) {
            $moneda = $pago->moneda_pago;
            $monto = number_format($pago->monto_pago, 2);
            $abonoUSD = number_format($pago->monto_en_factura, 2);
            $igtf = $pago->monto_igtf > 0 ? " (Inc. IGTF: {$pago->monto_igtf} {$moneda})" : "";
            $this->line(" * {$pago->metodoPago->nombre}: {$monto} {$moneda} -> Abono: $ {$abonoUSD} USD{$igtf}");
        }
        $this->line("<fg=bright-green;options=bold>=============================================</>");
        $this->line('');
    }
}
