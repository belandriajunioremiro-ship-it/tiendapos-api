<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TasaCambio;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Configuración")
 */
class TasaCambioController extends Controller
{
    /**
     * @OA\Get(
     *     path="/tasas-cambio",
     *     summary="Listar tasas de cambio activas",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Tasas activas por par de monedas")
     * )
     */
    public function index()
    {
        $tasas = TasaCambio::where('activa', true)
            ->orderBy('moneda_base')
            ->orderBy('moneda_destino')
            ->get();

        return response()->json(['data' => $tasas]);
    }

    /**
     * @OA\Post(
     *     path="/tasas-cambio",
     *     summary="Registrar tasa de cambio",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="moneda_base", type="string", example="USD"), @OA\Property(property="moneda_destino", type="string", example="VES"), @OA\Property(property="tasa", type="number"), @OA\Property(property="fuente", type="string", enum={"manual","BCV","paralelo","api_automatica","BCE"}), @OA\Property(property="fecha", type="string", format="date"))),
     *     @OA\Response(response=201, description="Tasa registrada")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'moneda_base'    => 'required|string|size:3',
            'moneda_destino' => 'required|string|size:3|different:moneda_base',
            'tasa'           => 'required|numeric|min:0.00000001',
            'fuente'         => 'required|string|in:manual,BCV,paralelo,api_automatica,BCE',
            'fecha'          => 'required|date',
            'notas'          => 'nullable|string',
        ]);

        // Desactivar tasa anterior para este par y fuente
        TasaCambio::where('moneda_base',    $validated['moneda_base'])
                  ->where('moneda_destino', $validated['moneda_destino'])
                  ->where('fuente',         $validated['fuente'])
                  ->update(['activa' => false]);

        $tasa = TasaCambio::create([...$validated, 'activa' => true]);

        return response()->json([
            'message' => 'Tasa de cambio registrada.',
            'data'    => $tasa,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/tasas-cambio/{id}",
     *     summary="Mostrar tasa de cambio",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tasa de cambio")
     * )
     */
    public function show($id)
    {
        return response()->json(['data' => TasaCambio::findOrFail($id)]);
    }

    /**
     * @OA\Put(
     *     path="/tasas-cambio/{id}",
     *     summary="Actualizar tasa de cambio",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="tasa", type="number"), @OA\Property(property="notas", type="string"))),
     *     @OA\Response(response=200, description="Tasa actualizada")
     * )
     */
    public function update(Request $request, $id)
    {
        $tasa = TasaCambio::findOrFail($id);

        $validated = $request->validate([
            'tasa'  => 'sometimes|numeric|min:0.00000001',
            'notas' => 'nullable|string',
        ]);

        $tasa->update($validated);

        return response()->json(['message' => 'Tasa actualizada.', 'data' => $tasa->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/tasas-cambio/{id}",
     *     summary="Desactivar tasa de cambio",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tasa desactivada")
     * )
     */
    public function destroy($id)
    {
        $tasa = TasaCambio::findOrFail($id);
        $tasa->update(['activa' => false]);

        return response()->json(['message' => 'Tasa desactivada.']);
    }
}