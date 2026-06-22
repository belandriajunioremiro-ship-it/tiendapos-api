<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovimientoCaja;
use App\Models\SesionCaja;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Cajas")
 */
class MovimientoCajaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/movimientos-caja",
     *     summary="Listar movimientos de caja",
     *     tags={"Cajas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="sesion_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="tipo", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Movimientos paginados")
     * )
     */
    public function index(Request $request)
    {
        $query = MovimientoCaja::with(['sesion.caja'])
            ->orderBy('creado_en', 'desc');

        if ($request->filled('sesion_id')) {
            $query->where('sesion_id', $request->sesion_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    /**
     * Registrar ingreso o egreso manual de caja (retiros, gastos, etc).
     *
     * @OA\Post(
     *     path="/movimientos-caja",
     *     summary="Registrar movimiento manual",
     *     tags={"Cajas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="sesion_id", type="integer"), @OA\Property(property="tipo", type="string", enum={"ingreso","retiro","gasto"}), @OA\Property(property="moneda", type="string"), @OA\Property(property="monto", type="number"), @OA\Property(property="concepto", type="string"), @OA\Property(property="tasa_base", type="number"), @OA\Property(property="referencia", type="string"))),
     *     @OA\Response(response=201, description="Movimiento registrado"),
     *     @OA\Response(response=422, description="Sesión cerrada")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sesion_id'    => 'required|integer|exists:sesiones_caja,id',
            'tipo'         => 'required|string|in:ingreso,retiro,gasto',
            'moneda'       => 'required|string|size:3',
            'monto'        => 'required|numeric|min:0.01',
            'tasa_base'    => 'nullable|numeric|min:0',
            'monto_en_base'=> 'nullable|numeric|min:0',
            'concepto'     => 'required|string|max:200',
            'referencia'   => 'nullable|string|max:100',
        ]);

        $sesion = SesionCaja::findOrFail($validated['sesion_id']);

        if ($sesion->estado !== 'abierta') {
            return response()->json(['message' => 'No se pueden registrar movimientos en una sesión cerrada.'], 422);
        }

        $movimiento = MovimientoCaja::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Movimiento de caja registrado.',
            'data'    => $movimiento,
        ], 201);
    }
}
