<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="TiendaPOS API",
 *     version="2.1.0",
 *     description="SaaS POS Multi-País para LATAM. Soporta 9 países (VE, CO, MX, EC, AR, PE, CL, BO, UY) con reglas fiscales, IVA, monedas y métodos de pago locales.",
 *     @OA\Contact(
 *         email="soporte@tiendapos.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Sanctum token de autenticación. Obténlo via POST /api/v1/auth/login"
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error message"),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data", type="object", nullable=true)
 * )
 */
abstract class Controller
{
    //
}
