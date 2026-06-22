<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdenCompra;
use App\Models\ItemOrdenCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Compras")
 */
class OrdenCompraController extends Controller
{
    /**
     * @OA\Get(
     *     path="/ordenes-compra",
     *     summary="Listar órdenes de compra",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="estado", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="proveedor_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Órdenes paginadas")
     * )
     */
    public function index(Request $request)
    {
        $query = OrdenCompra::with(['proveedor:id,razon_social', 'almacen:id,nombre'])
            ->orderBy('creado_en', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    /**
     * @OA\Post(
     *     path="/ordenes-compra",
     *     summary="Crear orden de compra",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="proveedor_id", type="integer"), @OA\Property(property="almacen_id", type="integer"), @OA\Property(property="moneda", type="string"), @OA\Property(property="items", type="array", @OA\Items(@OA\Property(property="producto_id", type="integer"), @OA\Property(property="cantidad", type="number"), @OA\Property(property="costo_unitario", type="number"), @OA\Property(property="impuesto_pct", type="number"))))),
     *     @OA\Response(response=201, description="Orden creada")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'proveedor_id'   => 'required|integer|exists:proveedores,id',
            'almacen_id'     => 'required|integer|exists:almacenes,id',
            'moneda'         => 'required|string|size:3',
            'fecha_estimada' => 'nullable|date|after:today',
            'notas'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.producto_id'    => 'required|integer|exists:productos,id',
            'items.*.variante_id'    => 'nullable|integer|exists:variantes_producto,id',
            'items.*.cantidad'       => 'required|numeric|min:0.0001',
            'items.*.costo_unitario' => 'required|numeric|min:0',
            'items.*.impuesto_pct'   => 'sometimes|numeric|min:0|max:100',
        ]);

        $orden = DB::transaction(function () use ($validated) {
            $numero = 'OC-' . strtoupper(uniqid());

            $subtotal = 0;
            $impuesto = 0;

            foreach ($validated['items'] as $item) {
                $subtotalLinea = $item['cantidad'] * $item['costo_unitario'];
                $impLinea      = $subtotalLinea * (($item['impuesto_pct'] ?? 0) / 100);
                $subtotal     += $subtotalLinea;
                $impuesto     += $impLinea;
            }

            $orden = OrdenCompra::create([
                'proveedor_id'   => $validated['proveedor_id'],
                'almacen_id'     => $validated['almacen_id'],
                'user_id'        => auth()->id(),
                'numero'         => $numero,
                'moneda'         => $validated['moneda'],
                'subtotal'       => round($subtotal, 4),
                'impuesto'       => round($impuesto, 4),
                'total'          => round($subtotal + $impuesto, 4),
                'fecha_estimada' => $validated['fecha_estimada'] ?? null,
                'notas'          => $validated['notas'] ?? null,
                'estado'         => 'borrador',
            ]);

            foreach ($validated['items'] as $item) {
                $subtotalLinea = $item['cantidad'] * $item['costo_unitario'];
                $impLinea      = $subtotalLinea * (($item['impuesto_pct'] ?? 0) / 100);

                ItemOrdenCompra::create([
                    'orden_id'       => $orden->id,
                    'producto_id'    => $item['producto_id'],
                    'variante_id'    => $item['variante_id'] ?? null,
                    'cantidad'       => $item['cantidad'],
                    'costo_unitario' => $item['costo_unitario'],
                    'impuesto_pct'   => $item['impuesto_pct'] ?? 0,
                    'total_linea'    => round($subtotalLinea + $impLinea, 4),
                ]);
            }

            return $orden;
        });

        return response()->json([
            'message' => 'Orden de compra creada.',
            'data'    => $orden->load('items'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/ordenes-compra/{id}",
     *     summary="Mostrar orden de compra",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Orden con items")
     * )
     */
    public function show($id)
    {
        return response()->json([
            'data' => OrdenCompra::with(['proveedor', 'almacen', 'items.producto', 'items.variante'])->findOrFail($id),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/ordenes-compra/{id}",
     *     summary="Actualizar orden de compra",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="estado", type="string", enum={"borrador","aprobada","cancelada"}), @OA\Property(property="fecha_estimada", type="string", format="date"), @OA\Property(property="notas", type="string"))),
     *     @OA\Response(response=200, description="Orden actualizada"),
     *     @OA\Response(response=422, description="Estado no editable")
     * )
     */
    public function update(Request $request, $id)
    {
        $orden = OrdenCompra::findOrFail($id);

        if (! in_array($orden->estado, ['borrador', 'aprobada'])) {
            return response()->json(['message' => 'No se puede editar una orden en estado: ' . $orden->estado], 422);
        }

        $validated = $request->validate([
            'estado'         => 'sometimes|string|in:borrador,aprobada,cancelada',
            'fecha_estimada' => 'nullable|date',
            'notas'          => 'nullable|string',
        ]);

        if (isset($validated['estado']) && $validated['estado'] === 'aprobada') {
            $validated['aprobado_por'] = auth()->id();
            $validated['aprobado_en']  = now();
        }

        $orden->update($validated);

        return response()->json(['message' => 'Orden actualizada.', 'data' => $orden->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/ordenes-compra/{id}",
     *     summary="Eliminar orden (solo borrador)",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Orden eliminada"),
     *     @OA\Response(response=422, description="Solo borradores eliminables")
     * )
     */
    public function destroy($id)
    {
        $orden = OrdenCompra::findOrFail($id);

        if ($orden->estado !== 'borrador') {
            return response()->json(['message' => 'Solo se pueden eliminar órdenes en borrador.'], 422);
        }

        DB::transaction(function () use ($orden) {
            $orden->items()->delete();
            $orden->delete();
        });

        return response()->json(['message' => 'Orden de compra eliminada.']);
    }
}