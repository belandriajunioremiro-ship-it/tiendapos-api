<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Compras", description="Gestión de proveedores, órdenes de compra y recepciones")
 */
class ProveedorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/proveedores",
     *     summary="Listar proveedores",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="buscar", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Proveedores paginados")
     * )
     */
    public function index(Request $request)
    {
        $query = Proveedor::where('activo', true);

        if ($request->filled('buscar')) {
            $term = $request->buscar;
            $query->where(fn($q) =>
                $q->where('razon_social', 'ilike', "%$term%")
                  ->orWhere('numero_documento', 'ilike', "%$term%")
            );
        }

        return response()->json($query->orderBy('razon_social')->paginate($request->get('per_page', 20)));
    }

    /**
     * @OA\Post(
     *     path="/proveedores",
     *     summary="Crear proveedor",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="razon_social", type="string"), @OA\Property(property="tipo_documento", type="string"), @OA\Property(property="numero_documento", type="string"), @OA\Property(property="contacto", type="string"), @OA\Property(property="telefono", type="string"), @OA\Property(property="email", type="string"), @OA\Property(property="moneda_compra", type="string"))),
     *     @OA\Response(response=201, description="Proveedor creado")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_documento'   => 'required|string|max:5',
            'numero_documento' => 'nullable|string|max:20',
            'razon_social'     => 'required|string|max:200',
            'nombre_comercial' => 'nullable|string|max:200',
            'contacto'         => 'nullable|string|max:100',
            'telefono'         => 'nullable|string|max:20',
            'telefono2'        => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:150',
            'direccion'        => 'nullable|string',
            'pais'             => 'sometimes|string|max:60',
            'moneda_compra'    => 'required|string|size:3',
            'dias_entrega'     => 'sometimes|integer|min:0',
            'credito_dias'     => 'sometimes|integer|min:0',
            'limite_credito'   => 'sometimes|numeric|min:0',
            'notas'            => 'nullable|string',
        ]);

        $proveedor = Proveedor::create($validated);
        return response()->json(['message' => 'Proveedor creado.', 'data' => $proveedor], 201);
    }

    /**
     * @OA\Get(
     *     path="/proveedores/{id}",
     *     summary="Mostrar proveedor",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Proveedor con productos asociados")
     * )
     */
    public function show($id)
    {
        return response()->json([
            'data' => Proveedor::with(['productos.producto:id,nombre,codigo_sku'])->findOrFail($id),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/proveedores/{id}",
     *     summary="Actualizar proveedor",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="razon_social", type="string"), @OA\Property(property="contacto", type="string"), @OA\Property(property="telefono", type="string"), @OA\Property(property="moneda_compra", type="string"))),
     *     @OA\Response(response=200, description="Proveedor actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $validated = $request->validate([
            'razon_social'     => 'sometimes|string|max:200',
            'nombre_comercial' => 'nullable|string|max:200',
            'contacto'         => 'nullable|string|max:100',
            'telefono'         => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:150',
            'moneda_compra'    => 'sometimes|string|size:3',
            'dias_entrega'     => 'sometimes|integer|min:0',
            'credito_dias'     => 'sometimes|integer|min:0',
            'limite_credito'   => 'sometimes|numeric|min:0',
            'notas'            => 'nullable|string',
            'activo'           => 'sometimes|boolean',
        ]);

        $proveedor->update($validated);
        return response()->json(['message' => 'Proveedor actualizado.', 'data' => $proveedor->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/proveedores/{id}",
     *     summary="Desactivar proveedor",
     *     tags={"Compras"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Proveedor desactivado")
     * )
     */
    public function destroy($id)
    {
        Proveedor::findOrFail($id)->update(['activo' => false]);
        return response()->json(['message' => 'Proveedor desactivado.']);
    }
}