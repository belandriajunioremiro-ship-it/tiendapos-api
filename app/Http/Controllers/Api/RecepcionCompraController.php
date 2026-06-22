<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use App\Models\ItemOrdenCompra;
use App\Models\MovimientoInventario;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\RecepcionCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Compras")
 */
class RecepcionCompraController extends Controller
{
    /**
     * @OA\Get(
     *     path="/recepciones",
     *     summary="Listar recepciones de compra",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Recepciones paginadas")
     * )
     */
    public function index(Request $request)
    {
        return response()->json(
            RecepcionCompra::with(['orden.proveedor', 'almacen'])->paginate($request->get('per_page', 20))
        );
    }

    /**
     * @OA\Post(
     *     path="/recepciones",
     *     summary="Registrar recepción de compra",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="orden_id", type="integer"), @OA\Property(property="almacen_id", type="integer"), @OA\Property(property="items", type="array", @OA\Items(@OA\Property(property="item_orden_id", type="integer"), @OA\Property(property="variante_id", type="integer"), @OA\Property(property="cantidad_esperada", type="number"), @OA\Property(property="cantidad_recibida", type="number"), @OA\Property(property="costo_unitario", type="number"), @OA\Property(property="lote", type="string"), @OA\Property(property="fecha_vencimiento", type="string", format="date"))))),
     *     @OA\Response(response=201, description="Recepción registrada. Inventario y costos actualizados.")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'orden_id'   => 'required|integer|exists:ordenes_compra,id',
            'almacen_id' => 'required|integer|exists:almacenes,id',
            'notas'      => 'nullable|string',
            'items'      => 'required|array|min:1',
            'items.*.item_orden_id'       => 'required|integer|exists:items_orden_compra,id',
            'items.*.variante_id'         => 'required|integer|exists:variantes_producto,id',
            'items.*.cantidad_esperada'   => 'required|numeric|min:0',
            'items.*.cantidad_recibida'   => 'required|numeric|min:0',
            'items.*.cantidad_rechazada'  => 'sometimes|numeric|min:0',
            'items.*.costo_unitario'      => 'required|numeric|min:0',
            'items.*.lote'                => 'nullable|string|max:50',
            'items.*.fecha_vencimiento'   => 'nullable|date',
        ]);

        $recepcion = DB::transaction(function () use ($validated) {
            $numero = 'RC-' . strtoupper(uniqid());

            $recepcion = RecepcionCompra::create([
                'orden_id'   => $validated['orden_id'],
                'almacen_id' => $validated['almacen_id'],
                'user_id'    => auth()->id(),
                'numero'     => $numero,
                'estado'     => 'procesada',
                'notas'      => $validated['notas'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $recepcion->items()->create($item);

                $cantRecibida = (float) $item['cantidad_recibida'];
                $costoUnit    = (float) $item['costo_unitario'];

                // ── ACTUALIZAR INVENTARIO ────────────────────────────────────
                $inv = Inventario::firstOrCreate(
                    ['variante_id' => $item['variante_id'], 'almacen_id' => $validated['almacen_id']],
                    ['cantidad_disponible' => 0, 'costo_promedio' => 0, 'stock_minimo' => 0]
                );

                $stockAnterior = $inv->cantidad_disponible;

                // COSTO PROMEDIO PONDERADO
                $costoPromNuevo = $inv->cantidad_disponible > 0
                    ? (($inv->cantidad_disponible * $inv->costo_promedio) + ($cantRecibida * $costoUnit))
                      / ($inv->cantidad_disponible + $cantRecibida)
                    : $costoUnit;

                $inv->update([
                    'cantidad_disponible' => $stockAnterior + $cantRecibida,
                    'costo_promedio'      => round($costoPromNuevo, 6),
                    'ultima_entrada'      => now(),
                ]);

                // ── MOVIMIENTO DE INVENTARIO ─────────────────────────────────
                MovimientoInventario::create([
                    'variante_id'    => $item['variante_id'],
                    'almacen_id'     => $validated['almacen_id'],
                    'user_id'        => auth()->id(),
                    'tipo'           => 'entrada_compra',
                    'cantidad'       => $cantRecibida,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo'    => $stockAnterior + $cantRecibida,
                    'costo_unitario' => $costoUnit,
                    'referencia_tipo'=> 'recepciones_compra',
                    'referencia_id'  => $recepcion->id,
                ]);

                // ── ACTUALIZAR COSTO_PROMEDIO EN PRODUCTO ────────────────────
                $itemOC = ItemOrdenCompra::find($item['item_orden_id']);
                if ($itemOC) {
                    $producto = Producto::find($itemOC->producto_id);
                    if ($producto) {
                        $producto->update(['costo_promedio' => round($costoPromNuevo, 6)]);
                    }

                    // Acumular recibido en la OC
                    $itemOC->increment('cantidad_recibida', $cantRecibida);
                }
            }

            // ── ACTUALIZAR ESTADO DE LA ORDEN COMPRA ────────────────────────
            $orden = OrdenCompra::with('items')->find($validated['orden_id']);
            $todoRecibido = $orden->items->every(fn($i) =>
                $i->cantidad_recibida >= $i->cantidad
            );
            $orden->update(['estado' => $todoRecibido ? 'recibida' : 'parcial']);

            return $recepcion;
        });

        return response()->json([
            'message' => 'Recepción confirmada. Inventario y costos actualizados.',
            'data'    => $recepcion->load('items'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/recepciones/{id}",
     *     summary="Mostrar recepción de compra",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Recepción con items")
     * )
     */
    public function show($id)
    {
        return response()->json([
            'data' => RecepcionCompra::with(['orden.proveedor', 'almacen', 'items.variante.producto'])->findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['message' => 'Las recepciones no se modifican. Cree un ajuste de inventario.'], 403);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'Las recepciones son inmutables por auditoría.'], 403);
    }
}
