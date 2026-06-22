<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCreditoRequest;
use App\Http\Requests\UpdateCreditoRequest;
use App\Http\Resources\CuentaCreditoResource;
use App\Models\CuentaCredito;
use App\Models\FacturaCredito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Créditos")
 */
class CreditoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/creditos",
     *     summary="Listar cuentas de crédito",
     *     tags={"Créditos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="estado", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="cliente_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Cuentas de crédito paginadas")
     * )
     */
    public function index(Request $request)
    {
        $query = CuentaCredito::with(['cliente:id,nombre,numero_documento,telefono'])
            ->where('saldo_usado', '>', 0)
            ->orderBy('saldo_usado', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        return CuentaCreditoResource::collection($query->paginate($request->get('per_page', 20)));
    }

    /**
     * @OA\Post(
     *     path="/creditos",
     *     summary="Crear cuenta de crédito",
     *     tags={"Créditos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="cliente_id", type="integer"), @OA\Property(property="moneda", type="string"), @OA\Property(property="limite", type="number"))),
     *     @OA\Response(response=201, description="Cuenta de crédito creada"),
     *     @OA\Response(response=422, description="Cliente ya tiene cuenta")
     * )
     */
    public function store(StoreCreditoRequest $request)
    {
        $validated = $request->validated();

        $existe = CuentaCredito::where('cliente_id', $validated['cliente_id'])->exists();
        if ($existe) {
            return response()->json([
                'message' => 'Este cliente ya tiene una cuenta de crédito.',
            ], 422);
        }

        $cuenta = CuentaCredito::create([
            'cliente_id'  => $validated['cliente_id'],
            'moneda'      => $validated['moneda'],
            'limite'      => $validated['limite'],
            'saldo_usado' => 0,
            'estado'      => 'activa',
        ]);

        return response()->json([
            'message' => 'Cuenta de crédito creada exitosamente.',
            'data'    => new CuentaCreditoResource($cuenta->load('cliente:id,nombre,numero_documento')),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/creditos/{id}",
     *     summary="Mostrar cuenta de crédito",
     *     tags={"Créditos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Cuenta con facturas y abonos")
     * )
     */
    public function show($id)
    {
        $cuenta = CuentaCredito::with([
            'cliente:id,nombre,numero_documento,telefono',
            'facturas' => fn($q) => $q->whereNotIn('estado', ['pagada', 'anulada'])
                ->with(['abonos.metodoPago:id,nombre'])
                ->orderBy('fecha_vence'),
        ])->findOrFail($id);

        $cuenta->facturas->each(function ($f) {
            if ($f->estado === 'pendiente' && $f->fecha_vence < now()->toDateString()) {
                $f->update(['estado' => 'vencida']);
            }
        });

        return response()->json(['data' => new CuentaCreditoResource($cuenta->fresh('facturas'))]);
    }

    /**
     * @OA\Put(
     *     path="/creditos/{id}",
     *     summary="Actualizar cuenta de crédito",
     *     tags={"Créditos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="limite", type="number"), @OA\Property(property="moneda", type="string"), @OA\Property(property="estado", type="string"))),
     *     @OA\Response(response=200, description="Cuenta actualizada"),
     *     @OA\Response(response=422, description="Límite no puede ser menor a saldo usado")
     * )
     */
    public function update(UpdateCreditoRequest $request, $id)
    {
        $cuenta = CuentaCredito::findOrFail($id);
        $validated = $request->validated();

        if (isset($validated['limite']) && $validated['limite'] < $cuenta->saldo_usado) {
            return response()->json([
                'message' => "El nuevo límite ({$validated['limite']}) no puede ser menor al saldo usado ({$cuenta->saldo_usado}).",
            ], 422);
        }

        $cuenta->update($validated);

        return response()->json([
            'message' => 'Cuenta de crédito actualizada.',
            'data'    => new CuentaCreditoResource($cuenta->fresh('cliente:id,nombre')),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/creditos/{id}",
     *     summary="Eliminar o bloquear cuenta de crédito",
     *     tags={"Créditos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Cuenta eliminada o bloqueada"),
     *     @OA\Response(response=422, description="Cuenta con saldo pendiente")
     * )
     */
    public function destroy($id)
    {
        $cuenta = CuentaCredito::findOrFail($id);

        if ($cuenta->saldo_usado > 0) {
            return response()->json([
                'message' => 'No se puede eliminar una cuenta con saldo pendiente ($' . $cuenta->saldo_usado . ').',
            ], 422);
        }

        $tieneFacturas = FacturaCredito::where('cuenta_credito_id', $cuenta->id)->exists();
        if ($tieneFacturas) {
            $cuenta->update(['estado' => 'bloqueada']);
            return response()->json([
                'message' => 'La cuenta tiene facturas históricas. Se marcó como bloqueada en lugar de eliminarla.',
            ]);
        }

        $cuenta->delete();

        return response()->json(['message' => 'Cuenta de crédito eliminada.']);
    }
}
