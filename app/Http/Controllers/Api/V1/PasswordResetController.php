<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * @OA\Tag(name="Auth")
 */
class PasswordResetController extends Controller
{
    /**
     * Envía un token de 6 caracteres al correo del usuario.
     *
     * @OA\Post(
     *     path="/auth/forgot-password",
     *     summary="Solicitar token de recuperación",
     *     tags={"Auth"},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="email", type="string"))),
     *     @OA\Response(response=200, description="Token enviado si el email existe")
     * )
     */
    public function enviarToken(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => true,
                'message' => 'Si el email existe, recibirás un código de recuperación.',
            ]);
        }

        $token = Str::random(6);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token'      => Hash::make($token),
                'created_at' => now(),
            ]
        );

        Mail::send('emails.reset-password', [
            'user'      => $user,
            'token'     => $token,
            'resetUrl'  => config('app.url') . '/reset-password?email=' . urlencode($request->email) . '&token=' . $token,
        ], function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Recuperación de Contraseña — TiendaPOS');
        });

        return response()->json([
            'success' => true,
            'message' => 'Si el email existe, recibirás un código de recuperación.',
        ]);
    }

    /**
     * Valida el token, actualiza la contraseña, elimina tokens Sanctum.
     *
     * @OA\Post(
     *     path="/auth/reset-password",
     *     summary="Restablecer contraseña",
     *     tags={"Auth"},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="email", type="string"), @OA\Property(property="token", type="string"), @OA\Property(property="password", type="string"), @OA\Property(property="password_confirmation", type="string"))),
     *     @OA\Response(response=200, description="Contraseña restablecida"),
     *     @OA\Response(response=400, description="Token inválido o expirado")
     * )
     */
    public function resetearPassword(ResetPasswordRequest $request): JsonResponse
    {
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $resetRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado.',
            ], 400);
        }

        if (! Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido.',
            ], 400);
        }

        $tokenCreatedAt = \Carbon\Carbon::parse($resetRecord->created_at);
        if (now()->diffInMinutes($tokenCreatedAt) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'El token ha expirado. Solicita uno nuevo.',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => $request->password]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña restablecida correctamente. Ya puedes iniciar sesión.',
        ]);
    }

    /**
     * Verifica si un token es válido (sin modificaciones).
     *
     * @OA\Post(
     *     path="/auth/verify-token",
     *     summary="Verificar token de recuperación",
     *     tags={"Auth"},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="email", type="string"), @OA\Property(property="token", type="string"))),
     *     @OA\Response(response=200, description="Token válido"),
     *     @OA\Response(response=400, description="Token inválido o expirado")
     * )
     */
    public function verificarToken(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $resetRecord || ! Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'valid'   => false,
                'message' => 'Token inválido',
            ], 400);
        }

        $tokenCreatedAt = \Carbon\Carbon::parse($resetRecord->created_at);
        if (now()->diffInMinutes($tokenCreatedAt) > 60) {
            return response()->json([
                'success' => false,
                'valid'   => false,
                'message' => 'Token expirado',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'valid'   => true,
            'message' => 'Token válido',
        ]);
    }
}
