<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAbonoRequest;
use App\Http\Resources\AbonoCreditoResource;
use App\Models\AbonoCredito;
use App\Models\CuentaCredito;
use App\Models\FacturaCredito;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Créditos", description="Gestión de créditos, cuentas y abonos")
 */
class AbonoController extends Controller
{
    /**
     * Listado de abonos registrados.
     *
     * @OA\Get(
     *     path="/abonos",
     *     summary="Listar abonos",
     *     tags={"Créditos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="factura_credito_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Abonos paginados")
     * )
     */
    public function index(Request $request)
    {
        $query = AbonoCredito::with(['factura.cliente:id,nombre', 'metodoPago:id,nombre'])
            ->orderBy('creado_en', 'desc');

        if ($request->filled('factura_credito_id')) {
            $query->where('factura_credito_id', $request->factura_credito_id);
        }

        return AbonoCreditoResource::collection($query->paginate($request->get('per_page', 20)));
    }

    /**
     * Registra un abono a una factura de crédito.
     * Reduce el saldo_pendiente y actualiza la cuenta_credito.
     *
     * @OA\Post(
     *     path="/abonos",
     *     summary="Registrar abono",
     *     tags={"Créditos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="factura_credito_id", type="integer"), @OA\Property(property="monto_en_moneda_cta", type="number"), @OA\Property(property="moneda_pago", type="string"), @OA\Property(property="monto_pago", type="number"), @OA\Property(property="metodo_pago_id", type="integer"), @OA\Property(property="tasa_base", type="number"), @OA\Property(property="referencia", type="string"), @OA\Property(property="sesion_caja_id", type="integer"))),
     *     @OA\Response(response=201, description="Abono registrado"),
     *     @OA\Response(response=422, description="Factura ya pagada o monto excede saldo")
     * )
     */
    public function store(StoreAbonoRequest $request)
    {
        $validated = $request->validated();

        $abono = DB::transaction(function () use ($validated) {
            $factura = FacturaCredito::lockForUpdate()->findOrFail($validated['factura_credito_id']);
            $cuenta  = CuentaCredito::lockForUpdate()->findOrFail($factura->cuenta_credito_id);

            if (in_array($factura->estado, ['pagada', 'anulada'])) {
                throw new \Exception("Esta factura ya está {$factura->estado}.");
            }

            $montoAbono = (float) $validated['monto_en_moneda_cta'];

            if ($montoAbono > $factura->saldo_pendiente) {
                throw new \Exception("El monto abonado ({$montoAbono}) supera el saldo pendiente ({$factura->saldo_pendiente}).");
            }

            // Crear el abono
            $abono = AbonoCredito::create([
                ...$validated,
                'user_id' => auth()->id(),
            ]);

            // Reducir saldo de la factura
            $nuevoSaldoFactura = $factura->saldo_pendiente - $montoAbono;
            $factura->update([
                'saldo_pendiente' => $nuevoSaldoFactura,
                'estado'          => $nuevoSaldoFactura <= 0 ? 'pagada' : 'parcial',
            ]);

            // Reducir saldo_usado en la cuenta de crédito
            $cuenta->decrement('saldo_usado', $montoAbono);

            // Registrar en movimientos_caja si hay sesión activa
            if ($validated['sesion_caja_id']) {
                MovimientoCaja::create([
                    'sesion_id'    => $validated['sesion_caja_id'],
                    'user_id'      => auth()->id(),
                    'tipo'         => 'ingreso',
                    'moneda'       => $validated['moneda_pago'],
                    'monto'        => $validated['monto_pago'],
                    'tasa_base'    => $validated['tasa_base'] ?? null,
                    'monto_en_base'=> $validated['monto_en_base'] ?? null,
                    'concepto'     => 'Abono crédito - Factura #' . $factura->id,
                    'referencia'   => $validated['referencia'] ?? null,
                ]);
            }

            return $abono;
        });

        return response()->json([
            'message' => 'Abono registrado exitosamente.',
            'data'    => new AbonoCreditoResource($abono->load('factura.cliente')),
        ], 201);
    }
}
