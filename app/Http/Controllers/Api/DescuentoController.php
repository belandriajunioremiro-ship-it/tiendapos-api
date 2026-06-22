<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Descuento;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Descuentos", description="Gestión de descuentos promocionales")
 */
class DescuentoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/descuentos",
     *     summary="Listar descuentos",
     *     tags={"Descuentos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="tipo_aplicacion", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Descuentos activos")
     * )
     */
    public function index(Request $request)
    {
        $query = Descuento::where('activo', true);

        if ($request->filled('tipo_aplicacion')) {
            $query->where('tipo_aplicacion', $request->tipo_aplicacion);
        }

        return response()->json(['data' => $query->orderBy('nombre')->get()]);
    }

    /**
     * @OA\Post(
     *     path="/descuentos",
     *     summary="Crear descuento",
     *     tags={"Descuentos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="tipo_aplicacion", type="string", enum={"producto","categoria","cliente","global"}), @OA\Property(property="valor_pct", type="number"), @OA\Property(property="fecha_inicio", type="string", format="date"), @OA\Property(property="fecha_fin", type="string", format="date"))),
     *     @OA\Response(response=201, description="Descuento creado")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'          => 'required|string|max:100',
            'tipo_aplicacion' => 'required|string|in:producto,categoria,cliente,global',
            'producto_id'     => 'nullable|integer|exists:productos,id',
            'categoria_id'    => 'nullable|integer|exists:categorias_productos,id',
            'cliente_id'      => 'nullable|integer|exists:clientes,id',
            'valor_pct'       => 'required|numeric|min:0|max:100',
            'maximo_pct'      => 'nullable|numeric|min:0|max:100',
            'fecha_inicio'    => 'nullable|date',
            'fecha_fin'       => 'nullable|date|after_or_equal:fecha_inicio',
            'activo'          => 'sometimes|boolean',
        ]);

        $descuento = Descuento::create($validated);
        return response()->json(['message' => 'Descuento creado.', 'data' => $descuento], 201);
    }

    /**
     * @OA\Get(
     *     path="/descuentos/{id}",
     *     summary="Mostrar descuento",
     *     tags={"Descuentos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Descuento")
     * )
     */
    public function show($id)
    {
        return response()->json(['data' => Descuento::findOrFail($id)]);
    }

    /**
     * @OA\Put(
     *     path="/descuentos/{id}",
     *     summary="Actualizar descuento",
     *     tags={"Descuentos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="valor_pct", type="number"), @OA\Property(property="fecha_inicio", type="string", format="date"), @OA\Property(property="fecha_fin", type="string", format="date"))),
     *     @OA\Response(response=200, description="Descuento actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $descuento = Descuento::findOrFail($id);
        $validated = $request->validate([
            'nombre'       => 'sometimes|string|max:100',
            'valor_pct'    => 'sometimes|numeric|min:0|max:100',
            'maximo_pct'   => 'nullable|numeric|min:0|max:100',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date',
            'activo'       => 'sometimes|boolean',
        ]);

        $descuento->update($validated);
        return response()->json(['message' => 'Descuento actualizado.', 'data' => $descuento->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/descuentos/{id}",
     *     summary="Desactivar descuento",
     *     tags={"Descuentos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Descuento desactivado")
     * )
     */
    public function destroy($id)
    {
        Descuento::findOrFail($id)->update(['activo' => false]);
        return response()->json(['message' => 'Descuento desactivado.']);
    }
}