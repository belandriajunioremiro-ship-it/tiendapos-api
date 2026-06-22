<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\SesionCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Cajas", description="Gestión de cajas y sesiones de cajero")
 */
class CajaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/cajas",
     *     summary="Listar cajas",
     *     tags={"Cajas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Cajas activas con última sesión")
     * )
     */
    public function index()
    {
        $cajas = Caja::with(['sesiones' => fn($q) => $q->where('estado', 'abierta')->latest()->limit(1)])
            ->where('activo', true)
            ->get();

        return response()->json(['data' => $cajas]);
    }

    /**
     * @OA\Post(
     *     path="/cajas",
     *     summary="Crear caja",
     *     tags={"Cajas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="descripcion", type="string"))),
     *     @OA\Response(response=201, description="Caja creada")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:80',
            'descripcion' => 'nullable|string|max:150',
            'activo'      => 'sometimes|boolean',
        ]);

        $caja = Caja::create($validated);

        return response()->json(['message' => 'Caja creada.', 'data' => $caja], 201);
    }

    /**
     * @OA\Get(
     *     path="/cajas/{id}",
     *     summary="Mostrar caja",
     *     tags={"Cajas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Caja con últimas 5 sesiones")
     * )
     */
    public function show($id)
    {
        $caja = Caja::with(['sesiones' => fn($q) => $q->latest()->limit(5)])->findOrFail($id);
        return response()->json(['data' => $caja]);
    }

    /**
     * @OA\Put(
     *     path="/cajas/{id}",
     *     summary="Actualizar caja",
     *     tags={"Cajas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="descripcion", type="string"))),
     *     @OA\Response(response=200, description="Caja actualizada")
     * )
     */
    public function update(Request $request, $id)
    {
        $caja = Caja::findOrFail($id);

        $validated = $request->validate([
            'nombre'      => 'sometimes|string|max:80',
            'descripcion' => 'nullable|string|max:150',
            'activo'      => 'sometimes|boolean',
        ]);

        $caja->update($validated);

        return response()->json(['message' => 'Caja actualizada.', 'data' => $caja->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/cajas/{id}",
     *     summary="Desactivar caja",
     *     tags={"Cajas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Caja desactivada")
     * )
     */
    public function destroy($id)
    {
        $caja = Caja::findOrFail($id);
        $caja->update(['activo' => false]);
        return response()->json(['message' => 'Caja desactivada.']);
    }

    /**
     * Abre una sesión de caja (turno de cajero).
     *
     * @OA\Post(
     *     path="/cajas/{caja}/abrir",
     *     summary="Abrir caja (iniciar turno)",
     *     tags={"Cajas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="caja", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="observaciones", type="string"))),
     *     @OA\Response(response=201, description="Caja abierta"),
     *     @OA\Response(response=409, description="Ya tiene una sesión abierta")
     * )
     */
    public function abrir(Request $request, $caja)
    {
        $cajaModel = Caja::where('activo', true)->findOrFail($caja);

        // Verificar que no haya sesión abierta
        $sesionActiva = SesionCaja::where('caja_id', $cajaModel->id)
            ->where('estado', 'abierta')
            ->first();

        if ($sesionActiva) {
            return response()->json([
                'message' => 'Esta caja ya tiene una sesión abierta.',
                'sesion'  => $sesionActiva,
            ], 409);
        }

        $validated = $request->validate([
            'observaciones' => 'nullable|string',
        ]);

        $sesion = SesionCaja::create([
            'caja_id'        => $cajaModel->id,
            'user_id'        => auth()->id(),
            'estado'         => 'abierta',
            'observaciones'  => $validated['observaciones'] ?? null,
            'apertura_en'    => now(),
        ]);

        return response()->json([
            'message' => 'Caja abierta correctamente.',
            'data'    => $sesion,
        ], 201);
    }

    /**
     * Cierra la sesión activa de la caja.
     *
     * @OA\Post(
     *     path="/cajas/{caja}/cerrar",
     *     summary="Cerrar caja (finalizar turno)",
     *     tags={"Cajas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="caja", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="observaciones", type="string"))),
     *     @OA\Response(response=200, description="Caja cerrada"),
     *     @OA\Response(response=404, description="No hay sesión abierta")
     * )
     */
    public function cerrar(Request $request, $caja)
    {
        $cajaModel = Caja::findOrFail($caja);

        $sesion = SesionCaja::where('caja_id', $cajaModel->id)
            ->where('estado', 'abierta')
            ->firstOrFail();

        $validated = $request->validate([
            'observaciones' => 'nullable|string',
        ]);

        DB::transaction(function () use ($sesion, $validated) {
            $sesion->update([
                'estado'        => 'cerrada',
                'cierre_en'     => now(),
                'observaciones' => $validated['observaciones'] ?? $sesion->observaciones,
            ]);
        });

        return response()->json([
            'message' => 'Caja cerrada correctamente.',
            'data'    => $sesion->fresh(),
        ]);
    }
}