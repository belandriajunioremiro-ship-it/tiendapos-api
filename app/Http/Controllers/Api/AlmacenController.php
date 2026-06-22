<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Inventario")
 */
class AlmacenController extends Controller
{
    /**
     * @OA\Get(
     *     path="/almacenes",
     *     summary="Listar almacenes",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista de almacenes activos")
     * )
     */
    public function index()
    {
        return response()->json(['data' => Almacen::where('activo', true)->orderBy('nombre')->get()]);
    }

    /**
     * @OA\Post(
     *     path="/almacenes",
     *     summary="Crear almacén",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="tipo", type="string", enum={"principal","sucursal","deposito","virtual"}), @OA\Property(property="direccion", type="string"), @OA\Property(property="responsable", type="string"))),
     *     @OA\Response(response=201, description="Almacén creado")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:100',
            'tipo'        => 'required|string|in:principal,sucursal,deposito,virtual',
            'direccion'   => 'nullable|string',
            'responsable' => 'nullable|string|max:100',
            'activo'      => 'sometimes|boolean',
        ]);

        $almacen = Almacen::create($validated);
        return response()->json(['message' => 'Almacén creado.', 'data' => $almacen], 201);
    }

    /**
     * @OA\Get(
     *     path="/almacenes/{id}",
     *     summary="Mostrar almacén",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Almacén")
     * )
     */
    public function show($id)
    {
        return response()->json(['data' => Almacen::findOrFail($id)]);
    }

    /**
     * @OA\Put(
     *     path="/almacenes/{id}",
     *     summary="Actualizar almacén",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="tipo", type="string"), @OA\Property(property="direccion", type="string"), @OA\Property(property="responsable", type="string"))),
     *     @OA\Response(response=200, description="Almacén actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $almacen = Almacen::findOrFail($id);
        $validated = $request->validate([
            'nombre'      => 'sometimes|string|max:100',
            'tipo'        => 'sometimes|string|in:principal,sucursal,deposito,virtual',
            'direccion'   => 'nullable|string',
            'responsable' => 'nullable|string|max:100',
            'activo'      => 'sometimes|boolean',
        ]);

        $almacen->update($validated);
        return response()->json(['message' => 'Almacén actualizado.', 'data' => $almacen->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/almacenes/{id}",
     *     summary="Desactivar almacén",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Almacén desactivado"),
     *     @OA\Response(response=422, description="Almacén con stock disponible")
     * )
     */
    public function destroy($id)
    {
        $almacen = Almacen::findOrFail($id);
        // Regla de negocio: No se puede desactivar si tiene inventario con stock
        $tieneStock = $almacen->inventarios()->where('cantidad_disponible', '>', 0)->exists();
        if ($tieneStock) {
            return response()->json(['message' => 'No se puede desactivar un almacén que aún tiene stock disponible.'], 422);
        }

        $almacen->update(['activo' => false]);
        return response()->json(['message' => 'Almacén desactivado.']);
    }
}
