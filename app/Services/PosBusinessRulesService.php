<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\ItemVenta;
use App\Models\MetodoPago;
use App\Models\PagoVenta;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Exception;

class StockInsuficienteException extends Exception {}

class PosBusinessRulesService
{
    /**
     * 🌍 NUEVO: Helper para obtener etiqueta fiscal según país de la tienda
     */
    private function etiquetaFiscal(string $clave, string $pais): string
    {
        $etiqueta = DB::table('etiquetas_fiscales_pais')
            ->where('pais', $pais)
            ->where('clave', $clave)
            ->value('etiqueta');
        return $etiqueta ?? $clave;
    }

    /**
     * REGLA 1: CÁLCULO DE IMPUESTO ADICIONAL SOBRE PAGO (IGTF en VE, 0 en otros)
     */
    public function registrarPagoConIgtf(Venta $venta, MetodoPago $metodoPago, float $montoPago, float $tasaAplicada)
    {
        $tienda = $venta->tienda;

        $montoIgtf = 0;
        $tasaIgtfPct = 0;

        // IGTF: solo para Venezuela y si el método de pago grava IGTF
        if ($tienda->pais === 'VE' && $tienda->es_agente_igtf && $metodoPago->grava_igtf) {
            $tasaIgtfPct = $tienda->alicuota_igtf; 
            $montoIgtf = round($montoPago * ($tasaIgtfPct / 100), 4);
        }

        $montoEnFactura = ($metodoPago->moneda === $venta->moneda_factura)
            ? $montoPago
            : round($montoPago * $tasaAplicada, 4);

        $montoIgtfEnFactura = 0;
        if ($montoIgtf > 0) {
            $montoIgtfEnFactura = ($metodoPago->moneda === $venta->moneda_factura)
                ? $montoIgtf
                : round($montoIgtf * $tasaAplicada, 4);
        }

        DB::transaction(function () use ($venta, $metodoPago, $montoPago, $tasaAplicada, $montoEnFactura, $montoIgtf, $tasaIgtfPct, $montoIgtfEnFactura) {
            
            PagoVenta::create([
                'venta_id'              => $venta->id,
                'metodo_pago_id'        => $metodoPago->id,
                'moneda_pago'           => $metodoPago->moneda,
                'monto_pago'            => $montoPago,
                'tasa_aplicada'         => $tasaAplicada,
                'monto_en_factura'      => $montoEnFactura,
                'monto_igtf'            => $montoIgtf,
                'tasa_igtf_pct'         => $tasaIgtfPct,
                'monto_igtf_en_factura' => $montoIgtfEnFactura,
            ]);

            $this->actualizarIgtfFactura($venta);
        });
    }

    private function actualizarIgtfFactura(Venta $venta)
    {
        $totalIgtfFactura = PagoVenta::where('venta_id', $venta->id)->sum('monto_igtf_en_factura');
        $nuevoTotal = ($venta->subtotal - $venta->descuento + $venta->impuesto_iva) + $totalIgtfFactura;

        $venta->update([
            'impuesto_igtf' => round($totalIgtfFactura, 4),
            'total'         => round($nuevoTotal, 4)
        ]);
    }

    /**
     * REGLA 2: FLUJO DE DOCUMENTOS Y MÁQUINA DE ESTADOS
     */
    public function procesarCotizacionAFactura(Venta $venta)
    {
        if ($venta->tipo_documento !== 'COT') {
            throw new Exception("El documento actual no es una Cotización.");
        }
        if ($venta->estado !== 'pendiente') {
            throw new Exception("Solo las cotizaciones pendientes pueden convertirse a factura.");
        }

        return DB::transaction(function () use ($venta) {
            $venta->update([
                'tipo_documento' => 'FAC',
                'estado'         => 'borrador', 
            ]);
            return $venta->fresh();
        });
    }

    public function procesarCobroFactura(Venta $venta)
    {
        if ($venta->tipo_documento === 'COT') {
            throw new Exception("No se puede cobrar una cotización directamente.");
        }
        if ($venta->estado === 'pagada') {
            throw new Exception("La factura ya se encuentra pagada.");
        }

        $totalPagadoEnFactura = PagoVenta::where('venta_id', $venta->id)->sum('monto_en_factura');
        $totalIgtfEnFactura   = PagoVenta::where('venta_id', $venta->id)->sum('monto_igtf_en_factura');
        $totalPagadoConIgtf   = $totalPagadoEnFactura + $totalIgtfEnFactura;
        
        if (round($totalPagadoConIgtf, 2) < round($venta->total, 2)) {
            throw new Exception("El monto pagado ({$totalPagadoConIgtf}) es insuficiente para cubrir el total ({$venta->total}).");
        }

        return DB::transaction(function () use ($venta) {
            $items = ItemVenta::where('venta_id', $venta->id)
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $inventario = Inventario::where('variante_id', $item->variante_id)
                    ->where('almacen_id', $venta->almacen_id)
                    ->lockForUpdate()
                    ->first();

                if (! $inventario) {
                    throw new Exception("No hay inventario para la variante {$item->variante_id} en el almacén {$venta->almacen_id}.");
                }

                if ($inventario->cantidad_disponible < $item->cantidad) {
                    throw new Exception(
                        "Stock insuficiente para la variante {$item->variante_id}: disponible {$inventario->cantidad_disponible}, requerido {$item->cantidad}."
                    );
                }

                $inventario->decrement('cantidad_disponible', $item->cantidad);
                $inventario->update(['ultima_salida' => now()]);
            }

            $venta->update(['estado' => 'pagada']);
            return $venta->fresh();
        });
    }

    /**
     * REGLA 3: REDONDEO FISCAL Y SNAPSHOT DE COSTOS/PRECIOS
     */
    public function agregarItemVenta(Venta $venta, $productoVariante, float $cantidad, float $precioUnitario, float $tasaConversion, float $descuentoPct, float $impuestoPct)
    {
        $inventario = Inventario::where('variante_id', $productoVariante->id)
            ->where('almacen_id', $venta->almacen_id)
            ->first();

        if (! $inventario) {
            throw new StockInsuficienteException("El producto no tiene inventario registrado en el almacén seleccionado.");
        }

        $itemsEnCarrito = ItemVenta::where('venta_id', $venta->id)
            ->where('variante_id', $productoVariante->id)
            ->sum('cantidad');

        $productoVariante->loadMissing('producto');
        $disponible = $inventario->cantidad_disponible - $itemsEnCarrito;

        if ($disponible < $cantidad) {
            $nombre = $productoVariante->producto->nombre ?? 'el producto';
            throw new StockInsuficienteException(
                "Stock insuficiente para {$nombre}: disponible {$disponible}, solicitado {$cantidad}."
            );
        }

        $precioEnFactura = round($precioUnitario * $tasaConversion, 6);
        
        $subtotalLineaBruto = $precioEnFactura * $cantidad;
        $montoDescuento = $subtotalLineaBruto * ($descuentoPct / 100);
        $baseImponibleLinea = $subtotalLineaBruto - $montoDescuento;
        
        $impuestoMontoLinea = round($baseImponibleLinea * ($impuestoPct / 100), 4);
        $totalLinea = $baseImponibleLinea + $impuestoMontoLinea;

        $costoUnitarioOriginal = $productoVariante->costo_promedio ?? $productoVariante->producto->costo_promedio ?? 0;
        $costoEnFactura = round($costoUnitarioOriginal * $tasaConversion, 6);

        return DB::transaction(function () use ($venta, $productoVariante, $cantidad, $precioUnitario, $tasaConversion, $descuentoPct, $impuestoPct, $precioEnFactura, $impuestoMontoLinea, $totalLinea, $costoUnitarioOriginal, $costoEnFactura) {
            
            ItemVenta::create([
                'venta_id'          => $venta->id,
                'variante_id'       => $productoVariante->id,
                'cantidad'          => $cantidad,
                'moneda_precio'     => $productoVariante->producto->moneda_precio,
                'precio_unitario'   => $precioUnitario,
                'costo_unitario'    => $costoUnitarioOriginal, 
                'tasa_conversion'   => $tasaConversion,
                'precio_en_factura' => $precioEnFactura,
                'costo_en_factura'  => $costoEnFactura,
                'descuento_pct'     => $descuentoPct,
                'impuesto_pct'      => $impuestoPct,
                'impuesto_monto'    => $impuestoMontoLinea, 
                'total_linea'       => round($totalLinea, 4)
            ]);

            $this->actualizarTotalesFactura($venta);
            return $venta->fresh();
        });
    }

    private function actualizarTotalesFactura(Venta $venta)
    {
        $totales = ItemVenta::where('venta_id', $venta->id)
            ->selectRaw('
                SUM(precio_en_factura * cantidad) as subtotal,
                SUM(precio_en_factura * cantidad * (descuento_pct / 100)) as descuento_total,
                SUM(impuesto_monto) as impuesto_total
            ')->first();

        $venta->update([
            'subtotal'     => round($totales->subtotal ?? 0, 4),
            'descuento'    => round($totales->descuento_total ?? 0, 4),
            'impuesto_iva' => round($totales->impuesto_total ?? 0, 4),
            'total'        => round(($totales->subtotal ?? 0) - ($totales->descuento_total ?? 0) + ($totales->impuesto_total ?? 0) + ($venta->impuesto_igtf ?? 0), 4)
        ]);
    }
}