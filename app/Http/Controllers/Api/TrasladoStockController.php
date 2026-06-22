<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\TrasladoStock;
use App\Models\ItemTraslado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Inventario")
 */
class TrasladoStockController extends Controller
{
    /**
     * @OA\Get(
     *     path="/traslados",
     *     summary="Listar traslados de stock",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="estado", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Traslados paginados")
     * )
     */
    public function index(Request $request)
    {
        $query = TrasladoStock::with(['origen:id,nombre', 'destino:id,nombre'])
            ->orderBy('creado_en', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    /**
     * @OA\Post(
     *     path="/traslados",
     *     summary="Crear traslado (borrador)",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="almacen_origen_id", type="integer"), @OA\Property(property="almacen_destino_id", type="integer"), @OA\Property(property="items", type="array", @OA\Items(@OA\Property(property="variante_id", type="integer"), @OA\Property(property="cantidad_enviada", type="number"), @OA\Property(property="costo_unitario", type="number"))))),
     *     @OA\Response(response=201, description="Traslado creado en borrador")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'almacen_origen_id'  => 'required|integer|exists:almacenes,id',
            'almacen_destino_id' => 'required|integer|exists:almacenes,id|different:almacen_origen_id',
            'notas'              => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.variante_id'        => 'required|integer|exists:variantes_producto,id',
            'items.*.cantidad_enviada'   => 'required|numeric|min:0.0001',
            'items.*.costo_unitario'     => 'nullable|numeric|min:0',
        ]);

        $numero = 'TR-' . strtoupper(uniqid());

        $traslado = TrasladoStock::create([
            'almacen_origen_id'  => $validated['almacen_origen_id'],
            'almacen_destino_id' => $validated['almacen_destino_id'],
            'user_id'            => auth()->id(),
            'numero'             => $numero,
            'estado'             => 'borrador',
            'notas'              => $validated['notas'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $traslado->items()->create([
                'variante_id'     => $item['variante_id'],
                'cantidad_enviada'=> $item['cantidad_enviada'],
                'costo_unitario'  => $item['costo_unitario'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Traslado creado en borrador.',
            'data'    => $traslado->load('items'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/traslados/{id}",
     *     summary="Mostrar traslado",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Traslado con items")
     * )
     */
    public function show($id)
    {
        return response()->json([
            'data' => TrasladoStock::with(['origen', 'destino', 'items.variante.producto'])->findOrFail($id),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/traslados/{id}",
     *     summary="Actualizar traslado (solo borrador)",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="notas", type="string"))),
     *     @OA\Response(response=200, description="Traslado actualizado"),
     *     @OA\Response(response=422, description="Solo borradores editables")
     * )
     */
    public function update(Request $request, $id)
    {
        $traslado = TrasladoStock::findOrFail($id);

        if ($traslado->estado !== 'borrador') {
            return response()->json(['message' => 'Solo se pueden editar traslados en borrador.'], 422);
        }

        $traslado->update($request->only(['notas']));
        return response()->json(['message' => 'Traslado actualizado.', 'data' => $traslado->fresh()]);
    }

    /**
     * Confirma el traslado: descuenta del origen, suma al destino, crea 2 movimientos.
     *
     * @OA\Post(
     *     path="/traslados/{id}/confirmar",
     *     summary="Confirmar traslado (ejecutar)",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Traslado confirmado y stock actualizado"),
     *     @OA\Response(response=422, description="Ya procesado")
     * )
     */
    public function confirmar($id)
    {
        $traslado = TrasladoStock::with('items')->findOrFail($id);

        if ($traslado->estado !== 'borrador') {
            return response()->json(['message' => 'El traslado ya fue procesado.'], 422);
        }

        DB::transaction(function () use ($traslado) {
            foreach ($traslado->items as $item) {

                // ── ORIGEN: restar stock ──────────────────────────────────────
                $invOrigen = Inventario::firstOrCreate(
                    ['variante_id' => $item->variante_id, 'almacen_id' => $traslado->almacen_origen_id],
                    ['cantidad_disponible' => 0, 'costo_promedio' => 0]
                );
                $stockOrigenAntes = $invOrigen->cantidad_disponible;
                $invOrigen->decrement('cantidad_disponible', $item->cantidad_enviada);
                $stockOrigenDespues = $invOrigen->fresh()->cantidad_disponible;

                MovimientoInventario::create([
                    'variante_id'    => $item->variante_id,
                    'almacen_id'     => $traslado->almacen_origen_id,
                    'user_id'        => auth()->id(),
                    'tipo'           => 'traslado_salida',
                    'cantidad'       => $item->cantidad_enviada,
                    'stock_anterior' => $stockOrigenAntes,
                    'stock_nuevo'    => $stockOrigenDespues,
                    'costo_unitario' => $item->costo_unitario,
                    'referencia_tipo'=> 'traslados_stock',
                    'referencia_id'  => $traslado->id,
                ]);

                // ── DESTINO: sumar stock ──────────────────────────────────────
                $invDestino = Inventario::firstOrCreate(
                    ['variante_id' => $item->variante_id, 'almacen_id' => $traslado->almacen_destino_id],
                    ['cantidad_disponible' => 0, 'costo_promedio' => 0]
                );
                $stockDestinoAntes = $invDestino->cantidad_disponible;
                $invDestino->increment('cantidad_disponible', $item->cantidad_enviada);
                $stockDestinoDespues = $invDestino->fresh()->cantidad_disponible;

                MovimientoInventario::create([
                    'variante_id'    => $item->variante_id,
                    'almacen_id'     => $traslado->almacen_destino_id,
                    'user_id'        => auth()->id(),
                    'tipo'           => 'traslado_entrada',
                    'cantidad'       => $item->cantidad_enviada,
                    'stock_anterior' => $stockDestinoAntes,
                    'stock_nuevo'    => $stockDestinoDespues,
                    'costo_unitario' => $item->costo_unitario,
                    'referencia_tipo'=> 'traslados_stock',
                    'referencia_id'  => $traslado->id,
                ]);

                // Marcar cantidad recibida en el item
                $item->update([
                    'cantidad_recibida' => $item->cantidad_enviada,
                ]);
            }

            $traslado->update([
                'estado'      => 'recibido',
                'recibido_por'=> auth()->id(),
                'recibido_en' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Traslado confirmado. Stock actualizado en ambos almacenes.',
            'data'    => $traslado->fresh()->load('items'),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/traslados/{id}",
     *     summary="Eliminar traslado (solo borrador)",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Traslado eliminado"),
     *     @OA\Response(response=422, description="Solo borradores eliminables")
     * )
     */
    public function destroy($id)
    {
        $traslado = TrasladoStock::findOrFail($id);

        if ($traslado->estado !== 'borrador') {
            return response()->json(['message' => 'Solo se pueden eliminar traslados en borrador.'], 422);
        }

        $traslado->items()->delete();
        $traslado->delete();
        return response()->json(['message' => 'Traslado eliminado.']);
    }
}
