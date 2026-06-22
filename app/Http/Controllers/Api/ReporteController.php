<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Reportes", description="Reportes analíticos y dashboard")
 */
class ReporteController extends Controller
{
    /**
     * Ventas por día del último mes (para gráfico de líneas).
     *
     * @OA\Get(
     *     path="/reportes/ventas",
     *     summary="Reporte de ventas por día",
     *     tags={"Reportes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="desde", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="hasta", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Ventas agrupadas por día")
     * )
     */
    public function ventas(Request $request)
    {
        $desde = $request->get('desde', now()->subDays(30)->toDateString());
        $hasta = $request->get('hasta', now()->toDateString());

        $data = DB::table('ventas')
            ->whereDate('creado_en', '>=', $desde)
            ->whereDate('creado_en', '<=', $hasta)
            ->whereIn('estado', ['pagada', 'parcial'])
            ->groupBy('fecha', 'moneda_factura')
            ->orderBy('fecha')
            ->select(
                DB::raw("DATE(creado_en) as fecha"),
                'moneda_factura',
                DB::raw('COUNT(*) as total_ventas'),
                DB::raw('SUM(total) as monto_total'),
                DB::raw('SUM(impuesto_iva) as total_iva'),
                DB::raw('SUM(impuesto_igtf) as total_igtf')
            )
            ->get();

        return response()->json(['data' => $data, 'desde' => $desde, 'hasta' => $hasta]);
    }

    /**
     * Top productos vendidos en un rango (para gráfico de barras).
     *
     * @OA\Get(
     *     path="/reportes/inventario",
     *     summary="Reporte de inventario (stock bajo)",
     *     tags={"Reportes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Inventario con alertas de stock mínimo")
     * )
     */
    public function inventario(Request $request)
    {
        $data = Inventario::with([
                'variante.producto:id,nombre,codigo_sku,moneda_precio',
                'almacen:id,nombre',
            ])
            ->select('*', DB::raw('cantidad_disponible - stock_minimo as diferencia_minimo'))
            ->orderBy('cantidad_disponible')
            ->paginate($request->get('per_page', 50));

        return response()->json($data);
    }

    /**
     * Rentabilidad por producto: ganancia acumulada en el período.
     *
     * @OA\Get(
     *     path="/reportes/rentabilidad",
     *     summary="Reporte de rentabilidad por producto",
     *     tags={"Reportes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="desde", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="hasta", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Rentabilidad por producto")
     * )
     */
    public function rentabilidad(Request $request)
    {
        $desde = $request->get('desde', now()->subDays(30)->toDateString());
        $hasta = $request->get('hasta', now()->toDateString());

        $data = DB::table('items_venta as iv')
            ->join('ventas as v', 'iv.venta_id', '=', 'v.id')
            ->join('variantes_producto as vp', 'iv.variante_id', '=', 'vp.id')
            ->join('productos as p', 'vp.producto_id', '=', 'p.id')
            ->whereDate('v.creado_en', '>=', $desde)
            ->whereDate('v.creado_en', '<=', $hasta)
            ->whereIn('v.estado', ['pagada', 'parcial'])
            ->groupBy('p.id', 'p.nombre', 'p.codigo_sku')
            ->orderByRaw('SUM(iv.ganancia_linea) DESC')
            ->limit(50)
            ->select(
                'p.id', 'p.nombre', 'p.codigo_sku',
                DB::raw('SUM(iv.cantidad) as unidades_vendidas'),
                DB::raw('SUM(iv.total_linea) as total_facturado'),
                DB::raw('SUM(iv.ganancia_linea) as ganancia_total'),
                DB::raw('ROUND(AVG(iv.descuento_pct), 2) as descuento_prom_pct')
            )
            ->get();

        return response()->json(['data' => $data, 'desde' => $desde, 'hasta' => $hasta]);
    }

    /**
     * Cartera de créditos vencidos y por vencer.
     *
     * @OA\Get(
     *     path="/reportes/creditos",
     *     summary="Reporte de cartera de créditos",
     *     tags={"Reportes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Créditos vencidos y por vencer")
     * )
     */
    public function creditos(Request $request)
    {
        $vencidas = DB::table('facturas_credito as fc')
            ->join('clientes as c', 'fc.cliente_id', '=', 'c.id')
            ->where('fc.estado', 'vencida')
            ->orWhere(fn($q) => $q->where('fc.estado', 'pendiente')->whereDate('fc.fecha_vence', '<', now()))
            ->select(
                'c.nombre as cliente', 'c.telefono',
                'fc.id as factura_id', 'fc.moneda',
                'fc.saldo_pendiente', 'fc.fecha_vence',
                DB::raw("CURRENT_DATE - fc.fecha_vence AS dias_vencida")
            )
            ->orderByRaw('fc.fecha_vence ASC')
            ->limit(100)
            ->get();

        $porVencer = DB::table('facturas_credito as fc')
            ->join('clientes as c', 'fc.cliente_id', '=', 'c.id')
            ->where('fc.estado', 'pendiente')
            ->whereDate('fc.fecha_vence', '>=', now())
            ->whereDate('fc.fecha_vence', '<=', now()->addDays(7))
            ->select(
                'c.nombre as cliente', 'c.telefono',
                'fc.id as factura_id', 'fc.moneda',
                'fc.saldo_pendiente', 'fc.fecha_vence',
                DB::raw("fc.fecha_vence - CURRENT_DATE AS dias_para_vencer")
            )
            ->orderBy('fc.fecha_vence')
            ->get();

        return response()->json([
            'vencidas'   => $vencidas,
            'por_vencer' => $porVencer,
        ]);
    }
}