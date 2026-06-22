<?php
namespace App\Services;

use App\Jobs\RecalcularCostoPromedioProducto;
use App\Models\Inventario;
use App\Models\InventarioLote;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Unidad;
use App\Models\VarianteProducto;
use App\Services\Inventory\DTO\RecepcionItemData;
use App\Services\Inventory\DTO\TrasladoItemData;
use App\Services\Inventory\DTO\VentaItemData;
use App\Services\Inventory\Exceptions\CoherenciaDimensionalException;
use App\Services\Inventory\Exceptions\ConfiguracionInventarioException;
use App\Services\Inventory\Exceptions\LoteRequeridoException;
use App\Services\Inventory\Exceptions\StockInsuficienteException;
use App\Services\Inventory\Results\RecepcionResult;
use App\Services\Inventory\Results\TrasladoResult;
use App\Services\Inventory\Results\VentaResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    // ════════════════════════════════════════════════════════════════════
    //  ENTRADAS — Recibir mercancía (compra, devolución de cliente, ajuste +)
    // ════════════════════════════════════════════════════════════════════

    public function recibir(RecepcionItemData $data): RecepcionResult
    {
        return DB::transaction(function () use ($data) {

            // 1) Cargar variante + producto + unidad origen + unidad base
            $variante = VarianteProducto::with('producto')->findOrFail($data->varianteId);
            $producto = $variante->producto;
            $unidadOrigen = Unidad::findOrFail($data->unidadOrigenId);
            $unidadBase = Unidad::findOrFail($producto->unidad_id);

            // 2) Validar coherencia dimensional
            $this->validarCoherenciaDimensional($unidadOrigen, $unidadBase, $variante->id);

            // 3) Validar consistencia de lotes según configuración del producto
            $this->validarConfiguracionLotes($producto, $data->lote, $data->fechaVencimiento);

            // 4) Resolver o crear el inventario (variante + almacen)
            $inventario = Inventario::firstOrCreate(
                [
                    'variante_id' => $variante->id,
                    'almacen_id'  => $data->almacenId,
                    'tienda_id'   => $variante->tienda_id,
                ],
                [
                    'cantidad_disponible' => 0,
                    'cantidad_reservada'  => 0,
                    'stock_minimo'        => 5,
                    'costo_promedio'      => 0,
                ]
            );

            // 5) Calcular magnitudes en unidad base
            $factor = (float) $unidadOrigen->factor_conversion;
            $cantidadBase = $data->cantidad * $factor;
            $costoPorUnidadBase = $data->costoUnitario / $factor;

            // 6) Actualizar stock + costo según modo (lote vs PPS)
            $loteId = null;

            if ($producto->controla_lotes) {
                // MODO FEFO: el costo vive en inventario_lotes
                // El trigger trg_sync_inventario_lotes actualizará inventario.cantidad_disponible
                $lote = InventarioLote::where('inventario_id', $inventario->id)
                    ->where('lote', $data->lote)
                    ->first();

                if ($lote) {
                    $lote->incrementar($cantidadBase, $costoPorUnidadBase);
                    $loteId = $lote->id;
                } else {
                    $lote = InventarioLote::create([
                        'tienda_id'         => $variante->tienda_id,
                        'inventario_id'     => $inventario->id,
                        'lote'              => $data->lote,
                        'fecha_vencimiento' => $data->fechaVencimiento,
                        'cantidad'          => $cantidadBase,
                        'costo_unitario'    => $costoPorUnidadBase,
                    ]);
                    $loteId = $lote->id;
                }
                // inventario.cantidad_disponible lo actualiza el trigger DB
            } else {
                // MODO PPS (Precio Promedio Simple): actualizar cantidad y recalcular costo
                $stockActual = $inventario->cantidad_disponible;
                $costoActual = $inventario->costo_promedio;

                $nuevoStock = $stockActual + $cantidadBase;
                $nuevoCosto = $nuevoStock > 0
                    ? (($stockActual * $costoActual) + ($cantidadBase * $costoPorUnidadBase)) / $nuevoStock
                    : $costoPorUnidadBase;

                $inventario->update([
                    'cantidad_disponible' => $nuevoStock,
                    'costo_promedio'      => round($nuevoCosto, 6),
                ]);
            }

            // 7) Registrar movimiento de inventario (para trazabilidad)
            $movimiento = MovimientoInventario::create([
                'tienda_id'       => $variante->tienda_id,
                'variante_id'     => $variante->id,
                'almacen_id'      => $data->almacenId,
                'user_id'         => $data->userId,
                'tipo'            => 'entrada',
                'cantidad'        => $cantidadBase,
                'stock_anterior'  => $inventario->getOriginal('cantidad_disponible') ?? 0,
                'stock_nuevo'     => $inventario->fresh()->cantidad_disponible,
                'costo_unitario'  => $costoPorUnidadBase,
                'referencia_tipo' => $data->referenciaTipo,
                'referencia_id'   => $data->referenciaId,
                'unidad_origen_id'  => $data->unidadOrigenId,
                'factor_snapshot'   => $factor,
                'cantidad_origen'   => $data->cantidad,
                'notas'             => $data->notas,
            ]);

            // 8) Disparar job asíncrono para recalcular costo cache en productos
            RecalcularCostoPromedioProducto::dispatch($producto->id);

            return new RecepcionResult(
                inventarioId: $inventario->id,
                cantidadBase: $cantidadBase,
                costoUnitarioBase: $costoPorUnidadBase,
                loteId: $loteId,
                movimientoId: $movimiento->id,
            );
        });
    }

    // ════════════════════════════════════════════════════════════════════
    //  SALIDAS — Vender (descuento FEFO o directo según controla_lotes)
    // ════════════════════════════════════════════════════════════════════

    public function vender(VentaItemData $data): VentaResult
    {
        return DB::transaction(function () use ($data) {

            $variante = VarianteProducto::with('producto')->findOrFail($data->varianteId);
            $producto = $variante->producto;
            $unidadVenta = Unidad::findOrFail($data->unidadVentaId);
            $unidadBase = Unidad::findOrFail($producto->unidad_id);

            // 1) Coherencia dimensional
            $this->validarCoherenciaDimensional($unidadVenta, $unidadBase, $variante->id);

            // 2) Calcular cantidad en unidad base
            $factor = (float) $unidadVenta->factor_conversion;
            $cantidadBase = $data->cantidadVenta * $factor;

            // 3) Resolver inventario
            $inventario = Inventario::where('variante_id', $variante->id)
                ->where('almacen_id', $data->almacenId)
                ->first();

            if (!$inventario || $inventario->cantidad_disponible < $cantidadBase) {
                throw StockInsuficienteException::forVariante(
                    $variante->id,
                    $cantidadBase,
                    $inventario?->cantidad_disponible ?? 0
                );
            }

            $lotesConsumidos = [];
            $costoUnitarioBase = $inventario->costo_promedio;

            if ($producto->controla_lotes) {
                // MODO FEFO: consumir lotes por fecha_vencimiento ASC
                [$lotesConsumidos, $costoUnitarioBase] = $this->consumirLotesFefo(
                    $inventario, $cantidadBase
                );
                // inventario.cantidad_disponible lo actualiza el trigger DB
            } else {
                // MODO PPS: descontar directo
                $inventario->decrement('cantidad_disponible', $cantidadBase);
            }

            // 4) Registrar movimiento
            $stockNuevo = $inventario->fresh()->cantidad_disponible;
            $movimiento = MovimientoInventario::create([
                'tienda_id'       => $variante->tienda_id,
                'variante_id'     => $variante->id,
                'almacen_id'      => $data->almacenId,
                'user_id'         => $data->userId,
                'tipo'            => 'salida',
                'cantidad'        => $cantidadBase,
                'stock_anterior'  => $inventario->cantidad_disponible,
                'stock_nuevo'     => $stockNuevo,
                'costo_unitario'  => $costoUnitarioBase,
                'referencia_tipo' => 'ventas',
                'referencia_id'   => $data->referenciaId,
                'unidad_origen_id'  => $data->unidadVentaId,
                'factor_snapshot'   => $factor,
                'cantidad_origen'   => $data->cantidadVenta,
                'notas'             => $data->notas,
            ]);

            RecalcularCostoPromedioProducto::dispatch($producto->id);

            return new VentaResult(
                movimientoId: $movimiento->id,
                cantidadBase: $cantidadBase,
                factorSnapshot: $factor,
                costoUnitarioBase: $costoUnitarioBase,
                lotesConsumidos: $lotesConsumidos,
            );
        });
    }

    // ════════════════════════════════════════════════════════════════════
    //  TRASLADOS — Mover mercancía entre almacenes
    // ════════════════════════════════════════════════════════════════════

    public function trasladar(TrasladoItemData $data): TrasladoResult
    {
        return DB::transaction(function () use ($data) {

            $variante = VarianteProducto::with('producto')->findOrFail($data->varianteId);
            $producto = $variante->producto;
            $unidad = Unidad::findOrFail($data->unidadId);
            $unidadBase = Unidad::findOrFail($producto->unidad_id);

            $this->validarCoherenciaDimensional($unidad, $unidadBase, $variante->id);

            $factor = (float) $unidad->factor_conversion;
            $cantidadBase = $data->cantidad * $factor;

            // Inventario origen
            $invOrigen = Inventario::where('variante_id', $variante->id)
                ->where('almacen_id', $data->almacenOrigenId)
                ->firstOrFail();

            if ($invOrigen->cantidad_disponible < $cantidadBase) {
                throw StockInsuficienteException::forVariante(
                    $variante->id, $cantidadBase, $invOrigen->cantidad_disponible
                );
            }

            // Inventario destino (crear si no existe)
            $invDestino = Inventario::firstOrCreate(
                ['variante_id' => $variante->id, 'almacen_id' => $data->almacenDestinoId, 'tienda_id' => $variante->tienda_id],
                ['cantidad_disponible' => 0, 'cantidad_reservada' => 0, 'costo_promedio' => $invOrigen->costo_promedio]
            );

            $loteOrigenId = null;
            $loteDestinoId = null;

            if ($producto->controla_lotes && $data->loteOrigenId) {
                // El trigger trg_traslado_replicar_lote está en items_traslado.
                // Como el InventoryService opera a bajo nivel directamente sobre el inventario
                // y bypassa items_traslado, debemos replicar el lote manualmente aquí.
                $loteOrigen = InventarioLote::findOrFail($data->loteOrigenId);
                $loteOrigen->descontar($cantidadBase);
                $loteOrigenId = $loteOrigen->id;

                $loteDestino = InventarioLote::firstOrCreate(
                    [
                        'inventario_id'     => $invDestino->id,
                        'lote'              => $loteOrigen->lote,
                        'fecha_vencimiento' => $loteOrigen->fecha_vencimiento,
                        'tienda_id'         => $variante->tienda_id,
                    ],
                    [
                        'tienda_id'      => $variante->tienda_id,
                        'cantidad'       => 0,
                        'costo_unitario' => $loteOrigen->costo_unitario,
                    ]
                );
                $loteDestino->incrementar($cantidadBase, $loteOrigen->costo_unitario);
                $loteDestinoId = $loteDestino->id;
            } else {
                $invOrigen->decrement('cantidad_disponible', $cantidadBase);
                $invDestino->increment('cantidad_disponible', $cantidadBase);
            }

            // Movimiento salida
            $movSalida = MovimientoInventario::create([
                'tienda_id'       => $variante->tienda_id,
                'variante_id'     => $variante->id,
                'almacen_id'      => $data->almacenOrigenId,
                'user_id'         => $data->userId,
                'tipo'            => 'traslado_salida',
                'cantidad'        => $cantidadBase,
                'stock_anterior'  => $invOrigen->cantidad_disponible,
                'stock_nuevo'     => $invOrigen->fresh()->cantidad_disponible,
                'costo_unitario'  => $invOrigen->costo_promedio,
                'referencia_tipo' => 'traslados_stock',
                'referencia_id'   => $data->referenciaId,
                'unidad_origen_id'  => $data->unidadId,
                'factor_snapshot'   => $factor,
                'cantidad_origen'   => $data->cantidad,
                'notas'             => $data->notas,
            ]);

            // Movimiento entrada
            $movEntrada = MovimientoInventario::create([
                'tienda_id'       => $variante->tienda_id,
                'variante_id'     => $variante->id,
                'almacen_id'      => $data->almacenDestinoId,
                'user_id'         => $data->userId,
                'tipo'            => 'traslado_entrada',
                'cantidad'        => $cantidadBase,
                'stock_anterior'  => $invDestino->getOriginal('cantidad_disponible') ?? 0,
                'stock_nuevo'     => $invDestino->fresh()->cantidad_disponible,
                'costo_unitario'  => $invOrigen->costo_promedio,
                'referencia_tipo' => 'traslados_stock',
                'referencia_id'   => $data->referenciaId,
                'unidad_origen_id'  => $data->unidadId,
                'factor_snapshot'   => $factor,
                'cantidad_origen'   => $data->cantidad,
            ]);

            return new TrasladoResult(
                movimientoSalidaId: $movSalida->id,
                movimientoEntradaId: $movEntrada->id,
                cantidadBase: $cantidadBase,
                factorSnapshot: $factor,
                loteOrigenId: $loteOrigenId,
                loteDestinoId: $loteDestinoId,
            );
        });
    }

    // ════════════════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS
    // ════════════════════════════════════════════════════════════════════

    private function validarCoherenciaDimensional(Unidad $origen, Unidad $base, int $varianteId): void
    {
        if ($origen->tipo !== $base->tipo) {
            throw CoherenciaDimensionalException::tiposNoCoinciden(
                $origen->tipo, $base->tipo, $varianteId
            );
        }
    }

    private function validarConfiguracionLotes(Producto $producto, ?string $lote, ?string $fechaVenc): void
    {
        if ($producto->controla_lotes && (!$lote || !$fechaVenc)) {
            throw LoteRequeridoException::productoControlaLotes($producto->id);
        }

        if (!$producto->controla_lotes && $lote) {
            throw LoteRequeridoException::productoNoControlaLotes($producto->id);
        }
    }

    /**
     * Consume lotes en orden FEFO (fecha_vencimiento ASC).
     * Retorna [$lotesConsumidos, $costoPromedioBase].
     */
    private function consumirLotesFefo(Inventario $inventario, float $cantidadNecesaria): array
    {
        $lotes = $inventario->lotes()->fefo()->get();
        $restante = $cantidadNecesaria;
        $lotesConsumidos = [];
        $costoTotal = 0.0;

        foreach ($lotes as $lote) {
            if ($restante <= 0) break;

            $consumir = min($lote->cantidad, $restante);
            $lote->descontar($consumir);

            $costoTotal += $consumir * (float) $lote->costo_unitario;
            $lotesConsumidos[] = [
                'lote_id'       => $lote->id,
                'lote'          => $lote->lote,
                'cantidad'      => $consumir,
                'costo_unitario'=> (float) $lote->costo_unitario,
            ];
            $restante -= $consumir;
        }

        if ($restante > 0) {
            throw StockInsuficienteException::forVariante(
                $inventario->variante_id,
                $cantidadNecesaria,
                $cantidadNecesaria - $restante
            );
        }

        $costoPromedioBase = $cantidadNecesaria > 0
            ? round($costoTotal / $cantidadNecesaria, 6)
            : 0.0;

        return [$lotesConsumidos, $costoPromedioBase];
    }
}
