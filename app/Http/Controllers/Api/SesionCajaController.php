<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SesionCaja;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Cajas")
 */
class SesionCajaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/sesiones-caja",
     *     summary="Listar sesiones de caja",
     *     tags={"Cajas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="estado", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="caja_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sesiones paginadas")
     * )
     */
    public function index(Request $request)
    {
        $query = SesionCaja::with(['caja:id,nombre'])
            ->orderBy('apertura_en', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        if ($request->filled('caja_id')) {
            $query->where('caja_id', $request->caja_id);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    /**
     * @OA\Get(
     *     path="/sesiones-caja/{id}",
     *     summary="Mostrar sesión de caja con movimientos y ventas",
     *     tags={"Cajas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sesión con movimientos y ventas")
     * )
     */
    public function show($id)
    {
        return response()->json([
            'data' => SesionCaja::with(['caja', 'movimientos.usuario:id,name', 'ventas' => fn($q) => $q->whereIn('estado', ['pagada', 'parcial'])])
                ->findOrFail($id)
        ]);
    }
}
