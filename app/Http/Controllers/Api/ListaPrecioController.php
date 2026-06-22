<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListaPrecio;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Precios", description="Listas de precios para productos")
 */
class ListaPrecioController extends Controller
{
    /**
     * @OA\Get(
     *     path="/listas-precio",
     *     summary="Listar listas de precio",
     *     tags={"Precios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Listas de precio activas")
     * )
     */
    public function index()
    {
        return response()->json(['data' => ListaPrecio::where('activo', true)->orderBy('nombre')->get()]);
    }

    /**
     * @OA\Post(
     *     path="/listas-precio",
     *     summary="Crear lista de precio",
     *     tags={"Precios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="tipo", type="string", enum={"porcentaje","monto_fijo"}), @OA\Property(property="valor", type="number"), @OA\Property(property="descripcion", type="string"))),
     *     @OA\Response(response=201, description="Lista de precios creada")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:100',
            'tipo'        => 'required|string|in:porcentaje,monto_fijo',
            'valor'       => 'required|numeric',
            'descripcion' => 'nullable|string|max:200',
            'activo'      => 'sometimes|boolean',
        ]);

        $lista = ListaPrecio::create($validated);
        return response()->json(['message' => 'Lista de precios creada.', 'data' => $lista], 201);
    }

    /**
     * @OA\Get(
     *     path="/listas-precio/{id}",
     *     summary="Mostrar lista de precio",
     *     tags={"Precios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lista de precio")
     * )
     */
    public function show($id)
    {
        return response()->json(['data' => ListaPrecio::findOrFail($id)]);
    }

    /**
     * @OA\Put(
     *     path="/listas-precio/{id}",
     *     summary="Actualizar lista de precio",
     *     tags={"Precios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="tipo", type="string"), @OA\Property(property="valor", type="number"), @OA\Property(property="descripcion", type="string"))),
     *     @OA\Response(response=200, description="Lista de precios actualizada")
     * )
     */
    public function update(Request $request, $id)
    {
        $lista = ListaPrecio::findOrFail($id);
        $validated = $request->validate([
            'nombre'      => 'sometimes|string|max:100',
            'tipo'        => 'sometimes|string|in:porcentaje,monto_fijo',
            'valor'       => 'sometimes|numeric',
            'descripcion' => 'nullable|string|max:200',
            'activo'      => 'sometimes|boolean',
        ]);

        $lista->update($validated);
        return response()->json(['message' => 'Lista de precios actualizada.', 'data' => $lista->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/listas-precio/{id}",
     *     summary="Desactivar lista de precio",
     *     tags={"Precios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lista de precios desactivada")
     * )
     */
    public function destroy($id)
    {
        ListaPrecio::findOrFail($id)->update(['activo' => false]);
        return response()->json(['message' => 'Lista de precios desactivada.']);
    }
}
