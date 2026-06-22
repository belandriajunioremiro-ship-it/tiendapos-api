<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Inventario")
 */
class InventarioController extends Controller
{
    /**
     * Stock actual por variante y almacén, con alertas de stock mínimo.
     *
     * @OA\Get(
     *     path="/inventario",
     *     summary="Listar inventario",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="almacen_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="stock_bajo", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="buscar", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=50)),
     *     @OA\Response(response=200, description="Inventario paginado")
     * )
     */
    public function index(Request $request)
    {
        $query = Inventario::with([
            'variante.producto:id,nombre,codigo_sku,moneda_precio',
            'almacen:id,nombre',
        ]);

        if ($request->filled('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->boolean('stock_bajo')) {
            $query->whereColumn('cantidad_disponible', '<=', 'stock_minimo');
        }

        if ($request->filled('buscar')) {
            $query->whereHas('variante.producto', function ($q) use ($request) {
                $q->where('nombre', 'ilike', '%' . $request->buscar . '%')
                  ->orWhere('codigo_sku', 'ilike', '%' . $request->buscar . '%');
            });
        }

        $inventario = $query->paginate($request->get('per_page', 50));

        return response()->json($inventario);
    }

    /**
     * @OA\Post(
     *     path="/inventario",
     *     summary="Registrar o actualizar stock",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="variante_id", type="integer"), @OA\Property(property="almacen_id", type="integer"), @OA\Property(property="cantidad_disponible", type="number"), @OA\Property(property="stock_minimo", type="number"), @OA\Property(property="costo_promedio", type="number"))),
     *     @OA\Response(response=201, description="Inventario registrado")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'variante_id'          => 'required|integer|exists:variantes_producto,id',
            'almacen_id'           => 'required|integer|exists:almacenes,id',
            'cantidad_disponible'  => 'required|numeric|min:0',
            'stock_minimo'         => 'sometimes|numeric|min:0',
            'stock_maximo'         => 'nullable|numeric|min:0',
            'costo_promedio'       => 'sometimes|numeric|min:0',
        ]);

        $inventario = Inventario::updateOrCreate(
            ['variante_id' => $validated['variante_id'], 'almacen_id' => $validated['almacen_id']],
            $validated
        );

        return response()->json(['message' => 'Inventario registrado.', 'data' => $inventario], 201);
    }

    /**
     * @OA\Get(
     *     path="/inventario/{id}",
     *     summary="Mostrar inventario de variante",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Registro de inventario")
     * )
     */
    public function show($id)
    {
        $inventario = Inventario::with([
            'variante.producto',
            'almacen',
        ])->findOrFail($id);

        return response()->json(['data' => $inventario]);
    }

    /**
     * @OA\Put(
     *     path="/inventario/{id}",
     *     summary="Actualizar inventario",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="cantidad_disponible", type="number"), @OA\Property(property="stock_minimo", type="number"), @OA\Property(property="costo_promedio", type="number"))),
     *     @OA\Response(response=200, description="Inventario actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $inventario = Inventario::findOrFail($id);

        $validated = $request->validate([
            'cantidad_disponible' => 'sometimes|numeric|min:0',
            'stock_minimo'        => 'sometimes|numeric|min:0',
            'stock_maximo'        => 'nullable|numeric|min:0',
            'costo_promedio'      => 'sometimes|numeric|min:0',
        ]);

        $inventario->update($validated);

        return response()->json(['message' => 'Inventario actualizado.', 'data' => $inventario->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/inventario/{id}",
     *     summary="Eliminar inventario (no permitido)",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=403, description="No permitido. Use ajustes de inventario.")
     * )
     */
    public function destroy($id)
    {
        // El inventario no se elimina, solo se puede ajustar a 0
        return response()->json(['message' => 'No permitido. Use ajustes de inventario para modificar el stock.'], 403);
    }
}