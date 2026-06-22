<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Onboarding\CuentaRequest;
use App\Http\Requests\Api\V1\Onboarding\DatosFiscalesRequest;
use App\Http\Requests\Api\V1\Onboarding\ConfigurarNegocioRequest;
use App\Http\Requests\Api\V1\Onboarding\PrimerProductoRequest;
use App\Services\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Onboarding", description="Registro y configuración inicial de la tienda en 4 pasos")
 */
class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingService $service
    ) {}

    /**
     * @OA\Post(
     *     path="/onboarding/cuenta",
     *     summary="Paso 1: Crear cuenta",
     *     description="Crea usuario + tienda + suscripción Trial. Devuelve token Sanctum para continuar.",
     *     tags={"Onboarding"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","pais"},
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@ejemplo.com"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass2026"),
     *             @OA\Property(property="pais", type="string", enum={"VE","CO","MX","EC","AR","PE","CL","BO","UY"}, example="VE")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Cuenta creada"),
     *     @OA\Response(response=422, description="Datos inválidos o email duplicado")
     * )
     */
    public function crearCuenta(CuentaRequest $request): JsonResponse
    {
        $result = $this->service->crearCuenta($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cuenta creada. Continúa con los datos fiscales.',
            'data'    => [
                'user' => [
                    'id'    => $result['user']->id,
                    'name'  => $result['user']->name,
                    'email' => $result['user']->email,
                ],
                'tienda' => [
                    'id'   => $result['tienda']->id,
                    'pais' => $result['tienda']->pais,
                ],
                'token'        => $result['token'],
                'paso_actual'  => $result['paso_actual'],
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/onboarding/estado",
     *     summary="Estado del onboarding",
     *     description="Devuelve el paso actual y progreso del onboarding.",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Estado del onboarding"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function estado(Request $request): JsonResponse
    {
        $tiendaId = $request->user()->tienda_id;
        $estado   = $this->service->obtenerEstado($tiendaId);

        return response()->json([
            'success' => true,
            'data'    => $estado,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/onboarding/datos-fiscales",
     *     summary="Paso 2: Datos fiscales",
     *     description="Guarda RIF/NIT/RFC y siembra automáticamente impuestos y monedas del país.",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"identificacion_fiscal","razon_social","direccion"},
     *             @OA\Property(property="identificacion_fiscal", type="string", example="J-12345678-9"),
     *             @OA\Property(property="razon_social", type="string", example="Mi Tienda C.A."),
     *             @OA\Property(property="nombre_comercial", type="string", example="Mi Tienda"),
     *             @OA\Property(property="direccion", type="string", example="Av. Principal, Edif. 123"),
     *             @OA\Property(property="telefono", type="string", example="+58-000-0000000"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="regimen_fiscal", type="string", example="Régimen General"),
     *             @OA\Property(property="codigo_postal", type="string", example="1010")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Datos fiscales guardados"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function datosFiscales(DatosFiscalesRequest $request): JsonResponse
    {
        $tiendaId = $request->user()->tienda_id;
        $tienda   = $this->service->guardarDatosFiscales($tiendaId, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Datos fiscales guardados. Impuestos y monedas configurados automáticamente.',
            'data'    => [
                'tienda'      => $tienda,
                'paso_actual' => 2,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/onboarding/configurar-negocio",
     *     summary="Paso 3: Configurar negocio",
     *     description="Crea almacén, caja, categorías, métodos de pago y cliente default.",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="tipo_negocio", type="string", enum={"farmacia","ferreteria","bodega","restaurante","licoreria","abarrotes","ropa","motos","general"}, example="abarrotes"),
     *             @OA\Property(property="nombre_almacen", type="string", example="Depósito Principal"),
     *             @OA\Property(property="nombre_caja", type="string", example="Caja 1"),
     *             @OA\Property(property="tipo_impresora", type="string", enum={"termica_80mm","termica_58mm","laser"}, example="termica_80mm")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Negocio configurado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function configurarNegocio(ConfigurarNegocioRequest $request): JsonResponse
    {
        $tiendaId = $request->user()->tienda_id;
        $this->service->configurarNegocio($tiendaId, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Negocio configurado. Catálogos creados. ¡Listo para facturar!',
            'data'    => [
                'paso_actual' => 3,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/onboarding/primer-producto",
     *     summary="Paso 4: Primer producto",
     *     description="Crea el primer producto con variante e inventario inicial. Completa el onboarding.",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string", example="Producto de prueba"),
     *             @OA\Property(property="sku", type="string", example="SKU-001"),
     *             @OA\Property(property="codigo_barra", type="string", example="1234567890123"),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="costo", type="number", format="float", example=10.50),
     *             @OA\Property(property="stock_inicial", type="integer", example=100),
     *             @OA\Property(property="aplica_iva", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Producto creado, onboarding completado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function primerProducto(PrimerProductoRequest $request): JsonResponse
    {
        $tiendaId = $request->user()->tienda_id;
        $producto = $this->service->crearPrimerProducto($tiendaId, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Producto creado. Onboarding completado.',
            'data'    => [
                'producto'              => $producto,
                'onboarding_completado' => true,
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/onboarding/saltar-primer-producto",
     *     summary="Saltar paso 4",
     *     description="Omite la creación del primer producto y completa el onboarding.",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Onboarding completado sin producto"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function saltarPrimerProducto(Request $request): JsonResponse
    {
        $this->service->saltarPrimerProducto($request->user()->tienda_id);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding completado. Puedes crear productos después.',
            'data'    => [
                'onboarding_completado' => true,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/onboarding/etiquetas/{pais}",
     *     summary="Etiquetas fiscales por país",
     *     description="Devuelve las etiquetas (RIF/NIT/RFC/RUC) y placeholders según el país.",
     *     tags={"Onboarding"},
     *     @OA\Parameter(name="pais", in="path", required=true,
     *         @OA\Schema(type="string", enum={"VE","CO","MX","EC","AR","PE","CL","BO","UY"})
     *     ),
     *     @OA\Response(response=200, description="Etiquetas del país")
     * )
     */
    public function etiquetasPais(string $pais): JsonResponse
    {
        $etiquetas = DB::table('etiquetas_fiscales_pais')
            ->where('pais', strtoupper($pais))
            ->get()
            ->keyBy('clave')
            ->map(fn($e) => [
                'etiqueta'    => $e->etiqueta,
                'placeholder' => $e->placeholder,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $etiquetas,
        ]);
    }
}
