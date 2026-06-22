<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VarianteProducto;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Productos")
 */
class VarianteController extends Controller
{
    /**
     * @OA\Post(
     *     path="/variantes",
     *     summary="Crear variante de producto",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="producto_id", type="integer"), @OA\Property(property="descripcion", type="string"), @OA\Property(property="codigo_barra", type="string"), @OA\Property(property="factor_unidad", type="number"))),
     *     @OA\Response(response=201, description="Variante creada")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'producto_id'   => 'required|integer|exists:productos,id',
            'codigo_barra'  => 'nullable|string|max:60|unique:variantes_producto,codigo_barra',
            'descripcion'   => 'required|string|max:200',
            'factor_unidad' => 'required|numeric|min:0.0001',
            'atributos'     => 'nullable|array',
            'activo'        => 'sometimes|boolean',
        ]);

        $variante = VarianteProducto::create($validated);

        return response()->json(['message' => 'Variante creada.', 'data' => $variante], 201);
    }

    /**
     * @OA\Put(
     *     path="/variantes/{id}",
     *     summary="Actualizar variante",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="descripcion", type="string"), @OA\Property(property="codigo_barra", type="string"), @OA\Property(property="factor_unidad", type="number"))),
     *     @OA\Response(response=200, description="Variante actualizada")
     * )
     */
    public function update(Request $request, $id)
    {
        $variante = VarianteProducto::findOrFail($id);
        $validated = $request->validate([
            'codigo_barra'  => 'nullable|string|max:60|unique:variantes_producto,codigo_barra,' . $id,
            'descripcion'   => 'sometimes|string|max:200',
            'factor_unidad' => 'sometimes|numeric|min:0.0001',
            'atributos'     => 'nullable|array',
            'activo'        => 'sometimes|boolean',
        ]);

        $variante->update($validated);

        return response()->json(['message' => 'Variante actualizada.', 'data' => $variante->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/variantes/{id}",
     *     summary="Desactivar variante",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Variante desactivada"),
     *     @OA\Response(response=422, description="Variante con stock activo")
     * )
     */
    public function destroy($id)
    {
        $variante = VarianteProducto::findOrFail($id);
        
        if ($variante->inventarios()->where('cantidad_disponible', '>', 0)->exists()) {
            return response()->json(['message' => 'No se puede desactivar una variante que tiene stock en inventario.'], 422);
        }

        $variante->update(['activo' => false]);
        return response()->json(['message' => 'Variante desactivada.']);
    }
}
