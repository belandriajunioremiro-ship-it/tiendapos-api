<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDevolucionRequest;
use App\Http\Resources\DevolucionResource;
use App\Models\DevolucionVenta;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Venta;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Ventas")
 */
class DevolucionController extends Controller
{
    public function __construct(private AuditoriaService $auditoria) {}
    /**
     * @OA\Get(
     *     path="/devoluciones",
     *     summary="Listar devoluciones",
     *     tags={"Ventas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Devoluciones paginadas")
     * )
     */
    public function index(Request $request)
    {
        $query = DevolucionVenta::with(['venta.cliente', 'items.itemVenta.variante.producto'])
            ->orderBy('creado_en', 'desc');

        return DevolucionResource::collection($query->paginate($request->get('per_page', 20)));
    }

    /**
     * Procesa una devolución y emite Nota de Crédito.
     *
     * @OA\Post(
     *     path="/devoluciones",
     *     summary="Registrar devolución",
     *     tags={"Ventas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="venta_id", type="integer"), @OA\Property(property="almacen_id", type="integer"), @OA\Property(property="motivo", type="string"), @OA\Property(property="tipo_reintegro", type="string", enum={"efectivo","transferencia","nota_credito"}), @OA\Property(property="moneda", type="string"), @OA\Property(property="notas", type="string"), @OA\Property(property="items", type="array", @OA\Items(@OA\Property(property="item_venta_id", type="integer"), @OA\Property(property="cantidad", type="number"))))),
     *     @OA\Response(response=201, description="Devolución procesada"),
     *     @OA\Response(response=422, description="Error de validación o negocio")
     * )
     */
    public function store(StoreDevolucionRequest $request)
    {
        $validated = $request->validated();

        $tienda = auth()->user()->tienda;

        try {
            $devolucion = DB::transaction(function () use ($validated, $tienda) {
                $venta = Venta::with('items')->findOrFail($validated['venta_id']);

                if (! in_array($venta->estado, ['pagada', 'parcial'])) {
                    throw new \Exception("Solo se pueden devolver ventas facturadas y pagadas.");
                }

                // Generar número de Nota de Crédito
                $numeroNc = 'NC-' . $tienda->prefijo_factura . '-' . strtoupper(uniqid());

                $totalDevueltoBase = 0;
                
                $devolucion = DevolucionVenta::create([
                    'venta_id'            => $venta->id,
                    'user_id'             => auth()->id(),
                    'almacen_id'          => $validated['almacen_id'],
                    'numero_nota_credito' => $numeroNc,
                    'motivo'              => $validated['motivo'],
                    'descripcion'         => $validated['notas'] ?? null,
                    'moneda_devolucion'   => $validated['moneda'],
                    'tipo_reintegro'      => $validated['tipo_reintegro'],
                    'estado'              => 'procesada',
                    'total_devuelto'      => 0, // Se calcula en el bucle
                ]);

                foreach ($validated['items'] as $it) {
                    $itemVenta = $venta->items->where('id', $it['item_venta_id'])->first();
                    if (!$itemVenta) {
                        throw new \Exception("El item proporcionado no pertenece a la venta.");
                    }

                    if ($it['cantidad'] > $itemVenta->cantidad) {
                        throw new \Exception("La cantidad a devolver ({$it['cantidad']}) supera lo facturado ({$itemVenta->cantidad}).");
                    }

                    // Calcular proporciones en base a la línea original
                    $precioUnit = $itemVenta->precio_en_factura;
                    $montoImpuestoProporcional = ($itemVenta->impuesto_monto / $itemVenta->cantidad) * $it['cantidad'];
                    $totalLineaDevolucion = ($precioUnit * $it['cantidad'] * (1 - ($itemVenta->descuento_pct / 100))) + $montoImpuestoProporcional;

                    $totalDevueltoBase += $totalLineaDevolucion;

                    // 1. Guardar Item de Devolución
                    $devolucion->items()->create([
                        'item_venta_id'             => $itemVenta->id,
                        'variante_id'               => $itemVenta->variante_id,
                        'cantidad'                  => $it['cantidad'],
                        'precio_unitario'           => $itemVenta->precio_unitario,
                        'monto_devuelto_en_factura' => round($totalLineaDevolucion, 4),
                        'impuesto_monto'            => round($montoImpuestoProporcional, 4),
                        'total_linea'               => round($totalLineaDevolucion, 4),
                        'motivo'                    => $validated['motivo'],
                    ]);

                    // 2. Reingresar al Inventario
                    $inv = Inventario::firstOrCreate(
                        ['variante_id' => $itemVenta->variante_id, 'almacen_id' => $validated['almacen_id']],
                        ['cantidad_disponible' => 0, 'costo_promedio' => 0]
                    );

                    $stockAnterior = $inv->cantidad_disponible;
                    $inv->increment('cantidad_disponible', $it['cantidad']);

                    // 3. Crear Movimiento de Inventario
                    MovimientoInventario::create([
                        'variante_id'    => $itemVenta->variante_id,
                        'almacen_id'     => $validated['almacen_id'],
                        'user_id'        => auth()->id(),
                        'tipo'           => 'devolucion_venta',
                        'cantidad'       => $it['cantidad'],
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo'    => $stockAnterior + $it['cantidad'],
                        'costo_unitario' => $itemVenta->costo_unitario,
                        'referencia_tipo'=> 'devoluciones_venta',
                        'referencia_id'  => $devolucion->id,
                        'notas'          => 'Devolución de venta #' . $venta->numero_factura,
                    ]);
                }

                $devolucion->update(['total_devuelto' => round($totalDevueltoBase, 4)]);

                $this->auditoria->registrar('crear_devolucion', 'devoluciones_venta', $devolucion->id, null, [
                    'venta_id' => $venta->id,
                    'total_devuelto' => round($totalDevueltoBase, 4),
                    'motivo' => $validated['motivo'],
                    'numero_nota_credito' => $numeroNc,
                ]);

                return $devolucion;
            });

            return response()->json([
                'message' => 'Devolución registrada. Nota de crédito generada e inventario restablecido.',
                'data'    => new DevolucionResource($devolucion->load(['items.itemVenta.variante.producto', 'venta.cliente'])),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar la devolución.',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/devoluciones/{id}",
     *     summary="Mostrar devolución",
     *     tags={"Ventas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Devolución con items y venta")
     * )
     */
    public function show($id)
    {
        return response()->json([
            'data' => new DevolucionResource(DevolucionVenta::with(['venta.cliente', 'items.itemVenta.variante.producto'])->findOrFail($id)),
        ]);
    }
}
