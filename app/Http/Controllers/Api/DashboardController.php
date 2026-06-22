<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use App\Models\Venta;
use App\Models\SesionCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Dashboard", description="KPIs y métricas del día")
 */
class DashboardController extends Controller
{
    /**
     * KPIs del día para el dashboard principal.
     *
     * @OA\Get(
     *     path="/dashboard",
     *     summary="KPIs del día",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Ventas hoy, stock bajo, sin stock, top productos")
     * )
     */
    public function index()
    {
        $hoy = now()->toDateString();

        // ── VENTAS DEL DÍA ──────────────────────────────────────────────────
        $ventasHoy = Venta::whereDate('creado_en', $hoy)
            ->whereIn('estado', ['pagada', 'parcial'])
            ->selectRaw('
                COUNT(*)                          AS total_transacciones,
                COALESCE(SUM(total), 0)           AS monto_total,
                COALESCE(SUM(impuesto_iva), 0)    AS total_iva,
                COALESCE(SUM(impuesto_igtf), 0)   AS total_igtf,
                moneda_factura
            ')
            ->groupBy('moneda_factura')
            ->get();

        // ── COBRADO EN CAJA (SESIONES ABIERTAS) ─────────────────────────────
        $sesionesAbiertas = SesionCaja::with(['caja:id,nombre'])
            ->where('estado', 'abierta')
            ->get(['id', 'caja_id', 'apertura_en', 'total_ventas_base']);

        // ── PRODUCTOS BAJO STOCK MÍNIMO ──────────────────────────────────────
        $stockBajo = Inventario::with([
                'variante.producto:id,nombre,codigo_sku',
                'almacen:id,nombre',
            ])
            ->whereColumn('cantidad_disponible', '<=', 'stock_minimo')
            ->where('cantidad_disponible', '>', 0)
            ->orderBy('cantidad_disponible')
            ->limit(20)
            ->get(['id', 'variante_id', 'almacen_id', 'cantidad_disponible', 'stock_minimo']);

        // ── PRODUCTOS SIN STOCK ──────────────────────────────────────────────
        $sinStock = Inventario::with([
                'variante.producto:id,nombre,codigo_sku',
                'almacen:id,nombre',
            ])
            ->where('cantidad_disponible', '<=', 0)
            ->limit(10)
            ->get(['id', 'variante_id', 'almacen_id', 'cantidad_disponible']);

        // ── TOP 5 PRODUCTOS DEL DÍA ──────────────────────────────────────────
        $topProductos = DB::table('items_venta as iv')
            ->join('ventas as v', 'iv.venta_id', '=', 'v.id')
            ->join('variantes_producto as vp', 'iv.variante_id', '=', 'vp.id')
            ->join('productos as p', 'vp.producto_id', '=', 'p.id')
            ->whereDate('v.creado_en', $hoy)
            ->whereIn('v.estado', ['pagada', 'parcial'])
            ->groupBy('p.id', 'p.nombre', 'p.codigo_sku')
            ->orderByRaw('SUM(iv.cantidad) DESC')
            ->limit(5)
            ->select(
                'p.id', 'p.nombre', 'p.codigo_sku',
                DB::raw('SUM(iv.cantidad) as total_vendido'),
                DB::raw('SUM(iv.total_linea) as total_facturado')
            )
            ->get();

        return response()->json([
            'ventas_hoy'       => $ventasHoy,
            'sesiones_abiertas'=> $sesionesAbiertas,
            'stock_bajo'       => $stockBajo,
            'sin_stock'        => $sinStock,
            'top_productos'    => $topProductos,
        ]);
    }
}
