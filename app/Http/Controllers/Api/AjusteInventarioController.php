<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AjusteInventario;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Inventario")
 */
class AjusteInventarioController extends Controller
{
    public function __construct(private AuditoriaService $auditoria) {}
    /**
     * @OA\Get(
     *     path="/ajustes",
     *     summary="Listar ajustes de inventario",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="estado", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Ajustes paginados")
     * )
     */
    public function index(Request $request)
    {
        $query = AjusteInventario::with(['almacen:id,nombre'])
            ->orderBy('creado_en', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    /**
     * @OA\Post(
     *     path="/ajustes",
     *     summary="Crear ajuste (borrador)",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="almacen_id", type="integer"), @OA\Property(property="motivo", type="string"), @OA\Property(property="items", type="array", @OA\Items(@OA\Property(property="variante_id", type="integer"), @OA\Property(property="cantidad_sistema", type="number"), @OA\Property(property="cantidad_fisica", type="number"), @OA\Property(property="costo_unitario", type="number"))))),
     *     @OA\Response(response=201, description="Ajuste creado en borrador")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'almacen_id'       => 'required|integer|exists:almacenes,id',
            'motivo'           => 'required|string|max:100',
            'notas'            => 'nullable|string',
            'items'            => 'required|array|min:1',
            'items.*.variante_id'       => 'required|integer|exists:variantes_producto,id',
            'items.*.cantidad_sistema'  => 'required|numeric|min:0',
            'items.*.cantidad_fisica'   => 'required|numeric|min:0',
            'items.*.costo_unitario'    => 'nullable|numeric|min:0',
            'items.*.notas'            => 'nullable|string',
        ]);

        $numero = 'AJ-' . strtoupper(uniqid());

        $ajuste = AjusteInventario::create([
            'almacen_id' => $validated['almacen_id'],
            'user_id'    => auth()->id(),
            'numero'     => $numero,
            'motivo'     => $validated['motivo'],
            'notas'      => $validated['notas'] ?? null,
            'estado'     => 'borrador',
        ]);

        foreach ($validated['items'] as $item) {
            $ajuste->items()->create($item);
        }

        return response()->json([
            'message' => 'Ajuste creado en borrador.',
            'data'    => $ajuste->load('items'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/ajustes/{id}",
     *     summary="Mostrar ajuste",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Ajuste con items")
     * )
     */
    public function show($id)
    {
        return response()->json([
            'data' => AjusteInventario::with(['almacen', 'items.variante.producto'])->findOrFail($id),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/ajustes/{id}",
     *     summary="Actualizar ajuste (solo borrador)",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="motivo", type="string"), @OA\Property(property="notas", type="string"))),
     *     @OA\Response(response=200, description="Ajuste actualizado"),
     *     @OA\Response(response=422, description="Solo borradores editables")
     * )
     */
    public function update(Request $request, $id)
    {
        $ajuste = AjusteInventario::findOrFail($id);

        if ($ajuste->estado !== 'borrador') {
            return response()->json(['message' => 'Solo se pueden editar ajustes en borrador.'], 422);
        }

        $ajuste->update($request->only(['motivo', 'notas']));

        return response()->json(['message' => 'Ajuste actualizado.', 'data' => $ajuste->fresh()]);
    }

    /**
     * Confirma el ajuste: actualiza inventario y crea movimientos.
     *
     * @OA\Post(
     *     path="/ajustes/{id}/confirmar",
     *     summary="Confirmar ajuste (ejecutar)",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Ajuste confirmado y stock actualizado"),
     *     @OA\Response(response=422, description="Ya procesado")
     * )
     */
    public function confirmar($id)
    {
        $ajuste = AjusteInventario::with('items')->findOrFail($id);

        if ($ajuste->estado !== 'borrador') {
            return response()->json(['message' => 'El ajuste ya fue procesado.'], 422);
        }

        DB::transaction(function () use ($ajuste) {
            foreach ($ajuste->items as $item) {
                $inv = Inventario::firstOrCreate(
                    ['variante_id' => $item->variante_id, 'almacen_id' => $ajuste->almacen_id],
                    ['cantidad_disponible' => 0, 'costo_promedio' => 0]
                );

                $stockAnterior = $inv->cantidad_disponible;
                $stockNuevo    = $item->cantidad_fisica; // el físico manda

                // Actualizar inventario
                $inv->update(['cantidad_disponible' => $stockNuevo]);

                // Bitácora inmutable
                MovimientoInventario::create([
                    'variante_id'    => $item->variante_id,
                    'almacen_id'     => $ajuste->almacen_id,
                    'user_id'        => auth()->id(),
                    'tipo'           => $stockNuevo >= $stockAnterior ? 'ajuste_positivo' : 'ajuste_negativo',
                    'cantidad'       => abs($stockNuevo - $stockAnterior),
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo'    => $stockNuevo,
                    'costo_unitario' => $item->costo_unitario,
                    'referencia_tipo'=> 'ajustes_inventario',
                    'referencia_id'  => $ajuste->id,
                    'notas'          => $ajuste->motivo,
                ]);
            }

            $ajuste->update([
                'estado'      => 'aprobado',
                'aprobado_por'=> auth()->id(),
                'aprobado_en' => now(),
            ]);

            $this->auditoria->registrar('ajustar_inventario', 'ajustes_inventario', $ajuste->id, null, AuditoriaService::snapshot($ajuste));
        });

        return response()->json([
            'message' => 'Ajuste confirmado y stock actualizado.',
            'data'    => $ajuste->fresh()->load('items'),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/ajustes/{id}",
     *     summary="Eliminar ajuste (solo borrador)",
     *     tags={"Inventario"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Ajuste eliminado"),
     *     @OA\Response(response=422, description="Solo borradores eliminables")
     * )
     */
    public function destroy($id)
    {
        $ajuste = AjusteInventario::findOrFail($id);

        if ($ajuste->estado !== 'borrador') {
            return response()->json(['message' => 'Solo se pueden eliminar ajustes en borrador.'], 422);
        }

        $ajuste->items()->delete();
        $ajuste->delete();

        return response()->json(['message' => 'Ajuste eliminado.']);
    }
}
