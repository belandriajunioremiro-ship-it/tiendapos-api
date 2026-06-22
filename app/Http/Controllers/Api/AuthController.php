<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\CambiarPasswordRequest;
use App\Http\Resources\UsuarioResource;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

/**
 * @OA\Tag(name="Autenticación", description="Login, logout, refresh y cambio de contraseña")
 */
class AuthController extends Controller
{
    public function __construct(private AuditoriaService $auditoria) {}

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Iniciar sesión",
     *     description="Autentica al usuario con email y contraseña. Devuelve un token Sanctum.",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@tienda.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="device_name", type="string", example="pos-terminal", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="1|sanctum_token_hash"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", nullable=true),
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Credenciales inválidas o cuenta inactiva")
     * )
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta está desactivada.'],
            ]);
        }

        $user->update(['ultimo_login' => now()]);

        $deviceName = $request->device_name ?? 'pos-token';
        $token = $user->createToken($deviceName, $this->getAbilities($user))->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'token'      => $token,
                'token_type'  => 'Bearer',
                'expires_in'  => config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null,
                'user'        => new UsuarioResource($user->load('roles', 'tienda')),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     summary="Perfil del usuario autenticado",
     *     description="Devuelve los datos del usuario actual con sus roles y tienda.",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Datos del usuario"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data'    => new UsuarioResource($request->user()->load('roles', 'tienda')),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     summary="Renovar token",
     *     description="Invalida el token actual y devuelve uno nuevo.",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Token renovado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        $token = $user->createToken('auth-token', $this->getAbilities($user))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token renovado',
            'data'    => [
                'token'       => $token,
                'token_type'  => 'Bearer',
                'expires_in'  => config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/auth/cambiar-password",
     *     summary="Cambiar contraseña",
     *     description="Cambia la contraseña del usuario autenticado.",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password_actual","password_nueva","password_nueva_confirmation"},
     *             @OA\Property(property="password_actual", type="string", format="password"),
     *             @OA\Property(property="password_nueva", type="string", format="password"),
     *             @OA\Property(property="password_nueva_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Contraseña actualizada"),
     *     @OA\Response(response=422, description="Contraseña actual incorrecta o validación fallida"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function cambiarPassword(CambiarPasswordRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->password_actual, $user->password)) {
            throw ValidationException::withMessages([
                'password_actual' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        $user->update([
            'password' => $request->password_nueva,
        ]);

        $this->auditoria->registrar('cambiar_password', 'users', $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Cerrar sesión",
     *     description="Invalida el token actual del usuario.",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Sesión cerrada"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cierre de sesión exitoso.',
        ]);
    }

    private function getAbilities($user): array
    {
        if ($user->hasRole('admin')) {
            return ['*'];
        } elseif ($user->hasRole('supervisor')) {
            return ['ventas', 'inventario', 'caja', 'creditos', 'reportes', 'devoluciones'];
        } elseif ($user->hasRole('cajero')) {
            return ['ventas', 'caja', 'clientes'];
        }

        return ['ventas'];
    }
}
