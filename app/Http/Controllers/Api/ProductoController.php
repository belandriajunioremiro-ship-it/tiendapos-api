<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use App\Models\VarianteProducto;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Productos", description="CRUD de productos con variantes e inventario")
 */
class ProductoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/productos",
     *     summary="Listar productos",
     *     description="Listado paginado de productos con filtros opcionales.",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="categoria_id", in="query", @OA\Schema(type="integer"), description="Filtrar por categoría"),
     *     @OA\Parameter(name="buscar", in="query", @OA\Schema(type="string"), description="Buscar por nombre o SKU"),
     *     @OA\Parameter(name="activo", in="query", @OA\Schema(type="boolean"), description="Filtrar por estado"),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20), description="Items por página"),
     *     @OA\Response(response=200, description="Lista paginada de productos"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index(Request $request)
    {
        $query = Producto::with(['variantes', 'categoria', 'impuesto'])
            ->where('activo', true);

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('buscar')) {
            $query->where('nombre', 'ilike', '%' . $request->buscar . '%')
                  ->orWhere('codigo_sku', 'ilike', '%' . $request->buscar . '%');
        }

        if ($request->filled('activo')) {
            $query->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
        }

        return ProductoResource::collection($query->orderBy('nombre')->paginate($request->get('per_page', 20)));
    }

    /**
     * @OA\Post(
     *     path="/productos",
     *     summary="Crear producto",
     *     description="Crea un nuevo producto con su variante por defecto.",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre","categoria_id","unidad_id","impuesto_id","moneda_precio"},
     *             @OA\Property(property="nombre", type="string", example="Laptop HP ProBook"),
     *             @OA\Property(property="codigo_sku", type="string", example="LAP-HP-001"),
     *             @OA\Property(property="categoria_id", type="integer", example=1),
     *             @OA\Property(property="unidad_id", type="integer", example=1),
     *             @OA\Property(property="impuesto_id", type="integer", example=1),
     *             @OA\Property(property="moneda_precio", type="string", example="USD"),
     *             @OA\Property(property="costo_promedio", type="number", format="float", example=250.00),
     *             @OA\Property(property="margen_pct", type="integer", example=30),
     *             @OA\Property(property="variante_codigo_barra", type="string"),
     *             @OA\Property(property="variante_descripcion", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Producto creado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function store(StoreProductoRequest $request)
    {
        $validated = $request->validated();

        $productoData = collect($validated)->except([
            'variante_codigo_barra', 'variante_descripcion'
        ])->toArray();

        $producto = Producto::create($productoData);

        VarianteProducto::create([
            'producto_id'  => $producto->id,
            'codigo_barra' => $validated['variante_codigo_barra'] ?? null,
            'descripcion'  => $validated['variante_descripcion'] ?? $producto->nombre,
            'factor_unidad'=> 1,
            'atributos'    => [],
            'activo'       => true,
        ]);

        return response()->json([
            'message' => 'Producto creado correctamente.',
            'data'    => new ProductoResource($producto->load('variantes')),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/productos/{id}",
     *     summary="Mostrar producto",
     *     description="Detalle de un producto con variantes, inventario por almacén, categoría e impuesto.",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle del producto"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function show($id)
    {
        $producto = Producto::with([
            'variantes.inventarios.almacen',
            'categoria',
            'impuesto',
            'margen',
        ])->findOrFail($id);

        return response()->json(['data' => new ProductoResource($producto)]);
    }

    /**
     * @OA\Put(
     *     path="/productos/{id}",
     *     summary="Actualizar producto",
     *     description="Actualiza los datos de un producto existente.",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string"),
     *             @OA\Property(property="codigo_sku", type="string"),
     *             @OA\Property(property="costo_promedio", type="number", format="float"),
     *             @OA\Property(property="margen_pct", type="integer"),
     *             @OA\Property(property="activo", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Producto actualizado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Producto no encontrado"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function update(UpdateProductoRequest $request, $id)
    {
        $producto = Producto::findOrFail($id);
        $validated = $request->validated();

        $producto->update($validated);

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'data'    => new ProductoResource($producto->fresh()->load('variantes')),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/productos/{id}",
     *     summary="Desactivar producto",
     *     description="Desactiva un producto (soft-delete lógico: activo=false).",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Producto desactivado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->update(['activo' => false]);

        return response()->json(['message' => 'Producto desactivado correctamente.']);
    }
}
