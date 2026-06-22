<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Impuesto;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Configuración")
 */
class ImpuestoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/impuestos",
     *     summary="Listar impuestos",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista de impuestos activos")
     * )
     */
    public function index()
    {
        return response()->json(['data' => Impuesto::where('activo', true)->orderBy('nombre')->get()]);
    }

    /**
     * @OA\Post(
     *     path="/impuestos",
     *     summary="Crear impuesto",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="porcentaje", type="number"), @OA\Property(property="tipo", type="string", enum={"iva","igtf","especifico","exento"}), @OA\Property(property="es_defecto", type="boolean"))),
     *     @OA\Response(response=201, description="Impuesto creado")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'     => 'required|string|max:60',
            'porcentaje' => 'required|numeric|min:0|max:100',
            'tipo'       => 'required|string|in:iva,igtf,especifico,exento',
            'aplica_a'   => 'sometimes|string|in:venta,compra,ambos',
            'es_defecto' => 'sometimes|boolean',
            'activo'     => 'sometimes|boolean',
        ]);

        if (! empty($validated['es_defecto'])) {
            Impuesto::where('es_defecto', true)->update(['es_defecto' => false]);
        }

        $impuesto = Impuesto::create($validated);
        return response()->json(['message' => 'Impuesto creado.', 'data' => $impuesto], 201);
    }

    /**
     * @OA\Get(
     *     path="/impuestos/{id}",
     *     summary="Mostrar impuesto",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Impuesto")
     * )
     */
    public function show($id)
    {
        return response()->json(['data' => Impuesto::findOrFail($id)]);
    }

    /**
     * @OA\Put(
     *     path="/impuestos/{id}",
     *     summary="Actualizar impuesto",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="porcentaje", type="number"), @OA\Property(property="tipo", type="string"))),
     *     @OA\Response(response=200, description="Impuesto actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $impuesto  = Impuesto::findOrFail($id);
        $validated = $request->validate([
            'nombre'     => 'sometimes|string|max:60',
            'porcentaje' => 'sometimes|numeric|min:0|max:100',
            'tipo'       => 'sometimes|string|in:iva,igtf,especifico,exento',
            'aplica_a'   => 'sometimes|string|in:venta,compra,ambos',
            'es_defecto' => 'sometimes|boolean',
            'activo'     => 'sometimes|boolean',
        ]);

        if (! empty($validated['es_defecto'])) {
            Impuesto::where('es_defecto', true)->where('id', '!=', $id)->update(['es_defecto' => false]);
        }

        $impuesto->update($validated);
        return response()->json(['message' => 'Impuesto actualizado.', 'data' => $impuesto->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/impuestos/{id}",
     *     summary="Desactivar impuesto",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Impuesto desactivado"),
     *     @OA\Response(response=422, description="No se puede desactivar impuesto por defecto")
     * )
     */
    public function destroy($id)
    {
        $impuesto = Impuesto::findOrFail($id);
        if ($impuesto->es_defecto) {
            return response()->json(['message' => 'No se puede desactivar el impuesto por defecto.'], 422);
        }
        $impuesto->update(['activo' => false]);
        return response()->json(['message' => 'Impuesto desactivado.']);
    }
}