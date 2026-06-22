<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Suscripcion\ActivarRequest;
use App\Services\SuscripcionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Suscripción", description="Gestión de planes y suscripciones")
 */
class SuscripcionController extends Controller
{
    public function __construct(private SuscripcionService $service) {}

    /**
     * @OA\Get(
     *     path="/suscripcion/estado",
     *     summary="Estado de la suscripción",
     *     description="Devuelve el estado actual, días restantes y límites del plan.",
     *     tags={"Suscripción"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Estado de la suscripción",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function estado(Request $request): JsonResponse
    {
        $estado = $this->service->estadoParaFrontend($request->user()->tienda_id);

        return response()->json([
            'success' => true,
            'data'    => $estado,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/suscripcion/planes",
     *     summary="Listar planes",
     *     description="Lista los planes disponibles para upgrade ordenados por precio.",
     *     tags={"Suscripción"},
     *     @OA\Response(response=200, description="Lista de planes")
     * )
     */
    public function planes(): JsonResponse
    {
        $planes = \App\Models\Plane::where('activo', true)
            ->orderBy('precio_mensual')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $planes,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/suscripcion/activar",
     *     summary="Activar plan",
     *     description="Activa un plan pagado. Cierra el trial e inicia un nuevo período.",
     *     tags={"Suscripción"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plan_id"},
     *             @OA\Property(property="plan_id", type="integer", example=2),
     *             @OA\Property(property="duracion_meses", type="integer", example=1, default=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Plan activado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function activar(ActivarRequest $request): JsonResponse
    {
        $suscripcion = $this->service->activarSuscripcion(
            $request->user()->tienda_id,
            $request->plan_id,
            $request->duracion_meses ?? 1
        );

        return response()->json([
            'success' => true,
            'message' => 'Suscripción activada correctamente.',
            'data'    => [
                'suscripcion_id' => $suscripcion->id,
                'plan'           => $suscripcion->plan->nombre,
                'estado'         => $suscripcion->estado,
                'fin_periodo'    => $suscripcion->fin_periodo,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/suscripcion/cancelar",
     *     summary="Cancelar suscripción",
     *     description="Cancela la suscripción activa. El acceso se mantiene hasta fin del período pagado.",
     *     tags={"Suscripción"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="motivo", type="string", example="Cambio a otro plan")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Suscripción cancelada"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function cancelar(Request $request): JsonResponse
    {
        $tiendaId = $request->user()->tienda_id;

        $suscripcion = $this->service->obtenerActiva($tiendaId);

        $suscripcion->update([
            'estado'             => 'cancelada',
            'cancelado_en'       => now(),
            'cancelado_por'      => $request->user()->id,
            'auto_renovar'       => false,
            'motivo_cancelacion' => $request->input('motivo', 'Cancelación por usuario'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Suscripción cancelada. El acceso se mantendrá hasta el fin del período pagado.',
        ]);
    }
}
