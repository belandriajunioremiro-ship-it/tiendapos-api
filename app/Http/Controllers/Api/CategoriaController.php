<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoriaProducto;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Productos")
 */
class CategoriaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/categorias",
     *     summary="Listar categorías",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista jerárquica de categorías")
     * )
     */
    public function index()
    {
        $categorias = CategoriaProducto::with('hijos')
            ->whereNull('padre_id')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json(['data' => $categorias]);
    }

    /**
     * @OA\Post(
     *     path="/categorias",
     *     summary="Crear categoría",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="slug", type="string"), @OA\Property(property="padre_id", type="integer"), @OA\Property(property="icono", type="string"))),
     *     @OA\Response(response=201, description="Categoría creada")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'padre_id' => 'nullable|integer|exists:categorias_productos,id',
            'nombre'   => 'required|string|max:100',
            'slug'     => 'required|string|max:120|unique:categorias_productos,slug',
            'nivel'    => 'sometimes|integer|min:1',
            'icono'    => 'nullable|string|max:60',
            'activo'   => 'sometimes|boolean',
        ]);

        $categoria = CategoriaProducto::create($validated);

        return response()->json([
            'message' => 'Categoría creada.',
            'data'    => $categoria,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/categorias/{id}",
     *     summary="Mostrar categoría",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Categoría con hijos y productos")
     * )
     */
    public function show($id)
    {
        $categoria = CategoriaProducto::with(['hijos', 'productos'])->findOrFail($id);
        return response()->json(['data' => $categoria]);
    }

    /**
     * @OA\Put(
     *     path="/categorias/{id}",
     *     summary="Actualizar categoría",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="slug", type="string"))),
     *     @OA\Response(response=200, description="Categoría actualizada")
     * )
     */
    public function update(Request $request, $id)
    {
        $categoria = CategoriaProducto::findOrFail($id);

        $validated = $request->validate([
            'padre_id' => 'nullable|integer|exists:categorias_productos,id',
            'nombre'   => 'sometimes|string|max:100',
            'slug'     => 'sometimes|string|max:120|unique:categorias_productos,slug,' . $id,
            'nivel'    => 'sometimes|integer|min:1',
            'icono'    => 'nullable|string|max:60',
            'activo'   => 'sometimes|boolean',
        ]);

        $categoria->update($validated);

        return response()->json(['message' => 'Categoría actualizada.', 'data' => $categoria->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/categorias/{id}",
     *     summary="Desactivar categoría",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Categoría desactivada")
     * )
     */
    public function destroy($id)
    {
        $categoria = CategoriaProducto::findOrFail($id);
        $categoria->update(['activo' => false]);
        return response()->json(['message' => 'Categoría desactivada.']);
    }
}