<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetodoPago;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Configuración")
 */
class MetodoPagoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/metodos-pago",
     *     summary="Listar métodos de pago",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Métodos de pago activos")
     * )
     */
    public function index()
    {
        return response()->json([
            'data' => MetodoPago::where('activo', true)->orderBy('nombre')->get()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/metodos-pago",
     *     summary="Crear método de pago",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="tipo", type="string", enum={"efectivo","transferencia","tarjeta_debito","tarjeta_credito","pago_movil","zelle","criptomoneda","cheque","nota_credito"}), @OA\Property(property="moneda", type="string"), @OA\Property(property="grava_igtf", type="boolean"))),
     *     @OA\Response(response=201, description="Método de pago creado")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('admin'); // Solo admin

        $validated = $request->validate([
            'nombre'              => 'required|string|max:60',
            'tipo'                => 'required|string|in:efectivo,transferencia,tarjeta_debito,tarjeta_credito,pago_movil,zelle,criptomoneda,cheque,nota_credito',
            'moneda'              => 'required|string|size:3',
            'requiere_referencia' => 'sometimes|boolean',
            'requiere_banco'      => 'sometimes|boolean',
            'grava_igtf'          => 'sometimes|boolean',
            'activo'              => 'sometimes|boolean',
        ]);

        $metodo = MetodoPago::create($validated);

        return response()->json(['message' => 'Método de pago creado.', 'data' => $metodo], 201);
    }

    /**
     * @OA\Get(
     *     path="/metodos-pago/{id}",
     *     summary="Mostrar método de pago",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Método de pago")
     * )
     */
    public function show($id)
    {
        return response()->json(['data' => MetodoPago::findOrFail($id)]);
    }

    /**
     * @OA\Put(
     *     path="/metodos-pago/{id}",
     *     summary="Actualizar método de pago",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="tipo", type="string"), @OA\Property(property="moneda", type="string"), @OA\Property(property="grava_igtf", type="boolean"))),
     *     @OA\Response(response=200, description="Método de pago actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $metodo = MetodoPago::findOrFail($id);

        $validated = $request->validate([
            'nombre'              => 'sometimes|string|max:60',
            'tipo'                => 'sometimes|string|in:efectivo,transferencia,tarjeta_debito,tarjeta_credito,pago_movil,zelle,criptomoneda,cheque,nota_credito',
            'moneda'              => 'sometimes|string|size:3',
            'requiere_referencia' => 'sometimes|boolean',
            'requiere_banco'      => 'sometimes|boolean',
            'grava_igtf'          => 'sometimes|boolean',
            'activo'              => 'sometimes|boolean',
        ]);

        $metodo->update($validated);

        return response()->json(['message' => 'Método de pago actualizado.', 'data' => $metodo->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/metodos-pago/{id}",
     *     summary="Desactivar método de pago",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Método de pago desactivado")
     * )
     */
    public function destroy($id)
    {
        $metodo = MetodoPago::findOrFail($id);
        $metodo->update(['activo' => false]);

        return response()->json(['message' => 'Método de pago desactivado.']);
    }
}