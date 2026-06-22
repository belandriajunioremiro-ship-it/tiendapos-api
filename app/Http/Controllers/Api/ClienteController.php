<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Clientes", description="Gestión de clientes")
 */
class ClienteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/clientes",
     *     summary="Listar clientes",
     *     tags={"Clientes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="buscar", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="tipo_cliente", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="activo", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lista paginada de clientes")
     * )
     */
    public function index(Request $request)
    {
        $query = Cliente::query();

        if ($request->filled('buscar')) {
            $term = $request->buscar;
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'ilike', '%' . $term . '%')
                  ->orWhere('numero_documento', 'ilike', '%' . $term . '%')
                  ->orWhere('telefono', 'ilike', '%' . $term . '%');
            });
        }

        if ($request->filled('tipo_cliente')) {
            $query->where('tipo_cliente', $request->tipo_cliente);
        }

        if ($request->filled('activo')) {
            $query->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
        }

        return ClienteResource::collection($query->orderBy('nombre')->paginate($request->get('per_page', 20)));
    }

    /**
     * @OA\Post(
     *     path="/clientes",
     *     summary="Crear cliente",
     *     tags={"Clientes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="tipo_documento", type="string"), @OA\Property(property="numero_documento", type="string"), @OA\Property(property="telefono", type="string"), @OA\Property(property="email", type="string"), @OA\Property(property="direccion", type="string"), @OA\Property(property="tipo_cliente", type="string"), @OA\Property(property="limite_credito", type="number"), @OA\Property(property="notas", type="string"))),
     *     @OA\Response(response=201, description="Cliente creado")
     * )
     */
    public function store(StoreClienteRequest $request)
    {
        $validated = $request->validated();

        $cliente = Cliente::create($validated);

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'data'    => new ClienteResource($cliente),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/clientes/{id}",
     *     summary="Mostrar cliente",
     *     tags={"Clientes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Cliente con últimas ventas")
     * )
     */
    public function show($id)
    {
        $cliente = Cliente::with(['ventas' => fn($q) => $q->latest()->limit(10)])
            ->findOrFail($id);

        return response()->json(['data' => new ClienteResource($cliente)]);
    }

    /**
     * @OA\Put(
     *     path="/clientes/{id}",
     *     summary="Actualizar cliente",
     *     tags={"Clientes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="tipo_documento", type="string"), @OA\Property(property="numero_documento", type="string"), @OA\Property(property="telefono", type="string"), @OA\Property(property="email", type="string"), @OA\Property(property="tipo_cliente", type="string"), @OA\Property(property="limite_credito", type="number"), @OA\Property(property="notas", type="string"))),
     *     @OA\Response(response=200, description="Cliente actualizado")
     * )
     */
    public function update(UpdateClienteRequest $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        $validated = $request->validated();

        $cliente->update($validated);

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'data'    => new ClienteResource($cliente->fresh()),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/clientes/{id}",
     *     summary="Desactivar cliente",
     *     tags={"Clientes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Cliente desactivado")
     * )
     */
    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->update(['activo' => false]);

        return response()->json(['message' => 'Cliente desactivado correctamente.']);
    }
}