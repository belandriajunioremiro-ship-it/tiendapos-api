<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MargenGanancia;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Precios")
 */
class MargenController extends Controller
{
    /**
     * @OA\Get(
     *     path="/margenes",
     *     summary="Listar márgenes de ganancia",
     *     tags={"Precios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Márgenes activos con categoría")
     * )
     */
    public function index()
    {
        return response()->json(['data' => MargenGanancia::with('categoria:id,nombre')->where('activo', true)->get()]);
    }

    /**
     * @OA\Post(
     *     path="/margenes",
     *     summary="Crear margen de ganancia",
     *     tags={"Precios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="porcentaje", type="number"), @OA\Property(property="tipo", type="string", enum={"markup","margin"}), @OA\Property(property="categoria_id", type="integer"), @OA\Property(property="es_defecto", type="boolean"))),
     *     @OA\Response(response=201, description="Margen creado")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria_id' => 'nullable|integer|exists:categorias_productos,id',
            'nombre'       => 'required|string|max:100',
            'porcentaje'   => 'required|numeric|min:0|max:9999',
            'tipo'         => 'required|string|in:markup,margin',
            'descripcion'  => 'nullable|string|max:200',
            'es_defecto'   => 'sometimes|boolean',
            'activo'       => 'sometimes|boolean',
        ]);

        if (! empty($validated['es_defecto'])) {
            MargenGanancia::where('es_defecto', true)->update(['es_defecto' => false]);
        }

        $margen = MargenGanancia::create($validated);
        return response()->json(['message' => 'Margen de ganancia creado.', 'data' => $margen], 201);
    }

    /**
     * @OA\Get(
     *     path="/margenes/{id}",
     *     summary="Mostrar margen de ganancia",
     *     tags={"Precios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Margen de ganancia")
     * )
     */
    public function show($id)
    {
        return response()->json(['data' => MargenGanancia::findOrFail($id)]);
    }

    /**
     * @OA\Put(
     *     path="/margenes/{id}",
     *     summary="Actualizar margen de ganancia",
     *     tags={"Precios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="porcentaje", type="number"), @OA\Property(property="tipo", type="string"), @OA\Property(property="es_defecto", type="boolean"))),
     *     @OA\Response(response=200, description="Margen actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $margen = MargenGanancia::findOrFail($id);
        $validated = $request->validate([
            'categoria_id' => 'nullable|integer|exists:categorias_productos,id',
            'nombre'       => 'sometimes|string|max:100',
            'porcentaje'   => 'sometimes|numeric|min:0|max:9999',
            'tipo'         => 'sometimes|string|in:markup,margin',
            'descripcion'  => 'nullable|string|max:200',
            'es_defecto'   => 'sometimes|boolean',
            'activo'       => 'sometimes|boolean',
        ]);

        if (! empty($validated['es_defecto'])) {
            MargenGanancia::where('es_defecto', true)->where('id', '!=', $id)->update(['es_defecto' => false]);
        }

        $margen->update($validated);
        return response()->json(['message' => 'Margen actualizado.', 'data' => $margen->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/margenes/{id}",
     *     summary="Desactivar margen de ganancia",
     *     tags={"Precios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Margen desactivado")
     * )
     */
    public function destroy($id)
    {
        MargenGanancia::findOrFail($id)->update(['activo' => false]);
        return response()->json(['message' => 'Margen desactivado.']);
    }
}
