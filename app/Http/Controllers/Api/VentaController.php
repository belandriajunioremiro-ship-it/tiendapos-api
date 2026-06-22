<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Resources\VentaResource;
use App\Models\MetodoPago;
use App\Models\Venta;
use App\Models\VarianteProducto;
use App\Services\AuditoriaService;
use App\Services\PosBusinessRulesService;
use App\Services\StockInsuficienteException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Ventas", description="Gestión de ventas POS")
 */
class VentaController extends Controller
{
    public function __construct(
        private PosBusinessRulesService $posService,
        private AuditoriaService $auditoria,
    ) {}

    /**
     * Listado paginado de ventas con filtros.
     *
     * @OA\Get(
     *     path="/ventas",
     *     summary="Listar ventas",
     *     tags={"Ventas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="estado", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="tipo_documento", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="cliente_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="desde", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="hasta", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=25)),
     *     @OA\Response(response=200, description="Lista paginada de ventas"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index(Request $request)
    {
        $query = Venta::with(['cliente:id,nombre', 'caja:id,nombre'])
            ->orderBy('creado_en', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_documento')) {
            $query->where('tipo_documento', $request->tipo_documento);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('desde')) {
            $query->whereDate('creado_en', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('creado_en', '<=', $request->hasta);
        }

        return VentaResource::collection($query->paginate($request->get('per_page', 25)));
    }

    /**
     * Procesa una venta completa desde el carrito del POS.
     *
     * Flujo:
     *   1. Crea la cabecera en estado 'borrador'.
     *   2. Por cada item del carrito → posService->agregarItemVenta() (snapshot + IGTF).
     *   3. Por cada pago → posService->registrarPagoConIgtf() (calcula IGTF por método).
     *   4. posService->procesarCobroFactura() → valida monto y pasa a 'pagada'.
     *
     * @OA\Post(
     *     path="/ventas",
     *     summary="Registrar venta POS",
     *     tags={"Ventas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="cliente_id", type="integer"), @OA\Property(property="caja_id", type="integer"), @OA\Property(property="sesion_caja_id", type="integer"), @OA\Property(property="almacen_id", type="integer"), @OA\Property(property="moneda_factura", type="string"), @OA\Property(property="tipo_documento", type="string"), @OA\Property(property="tipo_pago", type="string"), @OA\Property(property="items", type="array", @OA\Items(@OA\Property(property="variante_id", type="integer"), @OA\Property(property="cantidad", type="number"), @OA\Property(property="precio_unitario", type="number"), @OA\Property(property="tasa_conversion", type="number"), @OA\Property(property="descuento_pct", type="number"), @OA\Property(property="impuesto_pct", type="number"))), @OA\Property(property="pagos", type="array", @OA\Items(@OA\Property(property="metodo_pago_id", type="integer"), @OA\Property(property="monto_pago", type="number"), @OA\Property(property="tasa_aplicada", type="number"))))),
     *     @OA\Response(response=201, description="Venta registrada"),
     *     @OA\Response(response=422, description="Error de validación o stock insuficiente")
     * )
     */
    public function store(StoreVentaRequest $request)
    {
        $tienda = $request->user()->tienda;

        if (! $tienda) {
            return response()->json(['message' => 'Tienda no configurada.'], 500);
        }

        try {
            $venta = DB::transaction(function () use ($request, $tienda) {

                // ── 1. CREAR CABECERA EN BORRADOR ───────────────────────────────
                $numero = $tienda->prefijo_factura . '-' . str_pad($tienda->siguiente_numero, 6, '0', STR_PAD_LEFT);

                $venta = Venta::create([
                    'cliente_id'     => $request->cliente_id,
                    'caja_id'        => $request->caja_id,
                    'sesion_caja_id' => $request->sesion_caja_id,
                    'almacen_id'     => $request->almacen_id,
                    'user_id'        => auth()->id(),
                    'numero_factura' => $numero,
                    'tipo_documento' => $request->tipo_documento ?? 'FAC',
                    'tipo_pago'      => $request->tipo_pago ?? 'contado',
                    'moneda_factura' => $request->moneda_factura,
                    'fuente_tasa'    => $request->fuente_tasa,
                    'estado'         => 'borrador',
                    'notas'          => $request->notas,
                    'subtotal'       => 0,
                    'descuento'      => 0,
                    'impuesto_iva'   => 0,
                    'impuesto_igtf'  => 0,
                    'total'          => 0,
                ]);

                // Incrementar el correlativo de la tienda
                $tienda->increment('siguiente_numero');

                // ── 2. AGREGAR ITEMS (snapshot multimoneda + IVA) ───────────────
                foreach ($request->items as $item) {
                    $variante = VarianteProducto::with('producto')->findOrFail($item['variante_id']);

                    $this->posService->agregarItemVenta(
                        venta:          $venta,
                        productoVariante: $variante,
                        cantidad:       (float) $item['cantidad'],
                        precioUnitario: (float) $item['precio_unitario'],
                        tasaConversion: (float) $item['tasa_conversion'],
                        descuentoPct:   (float) ($item['descuento_pct'] ?? 0),
                        impuestoPct:    (float) ($item['impuesto_pct']  ?? 0),
                    );
                }

                // Recargar la venta con los totales actualizados por el service
                $venta->refresh();

                // ── 3. REGISTRAR PAGOS (IGTF automático por método) ────────────
                foreach ($request->pagos as $pago) {
                    $metodoPago = MetodoPago::findOrFail($pago['metodo_pago_id']);

                    $this->posService->registrarPagoConIgtf(
                        venta:        $venta,
                        metodoPago:   $metodoPago,
                        montoPago:    (float) $pago['monto_pago'],
                        tasaAplicada: (float) $pago['tasa_aplicada'],
                    );
                }

                // Recargar de nuevo para tener IGTF aplicado en el total
                $venta->refresh();

                // ── 4. CERRAR COBRO Y VALIDAR MONTO ────────────────────────────
                $this->posService->procesarCobroFactura($venta);

                return $venta->fresh()->load([
                    'cliente:id,nombre,numero_documento',
                    'items.variante.producto:id,nombre,codigo_sku',
                    'pagos.metodoPago:id,nombre,tipo',
                ]);
            });

            return response()->json([
                'message' => 'Venta registrada exitosamente.',
                'data'    => new VentaResource($venta),
            ], 201);

        } catch (StockInsuficienteException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar la venta.',
            ], 422);
        }
    }

    /**
     * Detalle completo de una venta con items y pagos.
     *
     * @OA\Get(
     *     path="/ventas/{id}",
     *     summary="Mostrar venta",
     *     tags={"Ventas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle de venta"),
     *     @OA\Response(response=404, description="Venta no encontrada")
     * )
     */
    public function show($id)
    {
        $venta = Venta::with([
            'cliente:id,nombre,tipo_documento,numero_documento,telefono',
            'caja:id,nombre',
            'items.variante.producto:id,nombre,codigo_sku,moneda_precio',
            'pagos.metodoPago:id,nombre,tipo,moneda',
        ])->findOrFail($id);

        return response()->json(['data' => new VentaResource($venta)]);
    }

    /**
     * Actualización parcial (solo campos de cabecera no fiscales).
     *
     * @OA\Put(
     *     path="/ventas/{id}",
     *     summary="Actualizar venta",
     *     tags={"Ventas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="notas", type="string"), @OA\Property(property="estado", type="string", enum={"borrador","pendiente","anulada"}))),
     *     @OA\Response(response=200, description="Venta actualizada"),
     *     @OA\Response(response=422, description="No se puede editar venta pagada")
     * )
     */
    public function update(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);

        if ($venta->estado === 'pagada') {
            return response()->json(['message' => 'No se puede editar una venta ya pagada.'], 422);
        }

        $validated = $request->validate([
            'notas'   => 'nullable|string',
            'estado'  => 'sometimes|string|in:borrador,pendiente,anulada',
        ]);

        $venta->update($validated);

        return response()->json([
            'message' => 'Venta actualizada.',
            'data'    => new VentaResource($venta->fresh()),
        ]);
    }

    /**
     * Anular una venta (no eliminar físicamente).
     *
     * @OA\Post(
     *     path="/ventas/{id}/anular",
     *     summary="Anular venta",
     *     tags={"Ventas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Venta anulada"),
     *     @OA\Response(response=422, description="Venta pagada no puede anularse")
     * )
     */
    public function destroy($id)
    {
        $venta = Venta::findOrFail($id);

        if ($venta->tienda_id !== auth()->user()->tienda_id) {
            return response()->json(['message' => 'No puedes anular ventas de otra tienda.'], 403);
        }

        if ($venta->estado === 'pagada') {
            return response()->json([
                'message' => 'Una venta pagada no puede anularse directamente. Use una devolución.',
            ], 422);
        }

        $snapshot = AuditoriaService::snapshot($venta, ['id', 'numero_factura', 'estado', 'total', 'cliente_id', 'user_id']);
        $venta->update(['estado' => 'anulada']);
        $this->auditoria->registrar('anular_venta', 'ventas', $venta->id, $snapshot, ['estado' => 'anulada']);

        return response()->json(['message' => 'Venta anulada correctamente.']);
    }
}