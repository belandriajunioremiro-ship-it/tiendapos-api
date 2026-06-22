<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Configuración")
 */
class TiendaController extends Controller
{
    public function __construct(private AuditoriaService $auditoria) {}
    /**
     * Devuelve la configuración de la tienda.
     *
     * @OA\Get(
     *     path="/tienda",
     *     summary="Obtener configuración de la tienda",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Configuración de la tienda"),
     *     @OA\Response(response=404, description="Tienda no configurada")
     * )
     */
    public function show(Request $request)
    {
        $tienda = $request->user()->tienda;

        if (! $tienda) {
            return response()->json([
                'success' => false,
                'message' => 'Tienda no configurada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $tienda,
        ]);
    }

    /**
     * Actualiza la configuración de la tienda (solo admin).
     *
     * @OA\Put(
     *     path="/tienda",
     *     summary="Actualizar configuración de la tienda",
     *     tags={"Configuración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="razon_social", type="string"), @OA\Property(property="nombre_comercial", type="string"), @OA\Property(property="direccion", type="string"), @OA\Property(property="telefono", type="string"), @OA\Property(property="email", type="string"), @OA\Property(property="prefijo_factura", type="string"), @OA\Property(property="decimales_precio", type="integer"), @OA\Property(property="es_agente_igtf", type="boolean"), @OA\Property(property="alicuota_igtf", type="number"))),
     *     @OA\Response(response=200, description="Tienda actualizada"),
     *     @OA\Response(response=404, description="Tienda no encontrada")
     * )
     */
    public function update(Request $request)
    {
        $tienda = $request->user()->tienda;

        if (! $tienda) {
            return response()->json([
                'success' => false,
                'message' => 'Tienda no encontrada.',
            ], 404);
        }

        $validated = $request->validate([
            'rif'              => 'sometimes|string|max:20',
            'razon_social'     => 'sometimes|string|max:200',
            'nombre_comercial' => 'nullable|string|max:200',
            'direccion'        => 'nullable|string',
            'telefono'         => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:150',
            'logo_url'         => 'nullable|url|max:500',
            'zona_horaria'     => 'sometimes|string|max:50',
            'prefijo_factura'  => 'sometimes|string|max:10',
            'decimales_precio' => 'sometimes|integer|min:0|max:4',
            'es_agente_igtf'   => 'sometimes|boolean',
            'alicuota_igtf'    => 'sometimes|numeric|min:0|max:100',
            'activo'           => 'sometimes|boolean',
        ]);

        $snapshot = AuditoriaService::snapshot($tienda, array_keys($validated));
        $tienda->update($validated);

        $this->auditoria->registrar('editar_tienda', 'tienda', $tienda->id, $snapshot, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Tienda actualizada correctamente.',
            'data'    => $tienda->fresh(),
        ]);
    }
}