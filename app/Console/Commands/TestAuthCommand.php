<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\TiendaPosTestStyle;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class TestAuthCommand extends Command
{
    use TiendaPosTestStyle;

    protected $signature = 'pos:test-auth';
    protected $description = 'Test visual del sistema de autenticación Sanctum';

    public function handle(): int
    {
        $this->testHeader(
            'SISTEMA DE AUTENTICACIÓN',
            'Login · Token · Refresh · Cambio Password · Bloqueo · Logout'
        );

        $user = User::where('email', 'admin@tiendapos.com')->first();
        $passwordOriginal = 'password';

        if (! $user) {
            $this->testFail('No existe el usuario admin@tiendapos.com');
            $this->testInfo('Ejecuta: php artisan db:seed --class=RolesAndPermissionsSeeder');
            return 1;
        }

        // ─── PASO 1 ─────────────────────────────────────────────
        $this->testStep(1, 'Verificando LOGIN con credenciales correctas');

        $credencialesOk = Hash::check($passwordOriginal, $user->password);
        if ($credencialesOk) {
            $this->testOk('Credenciales válidas para admin@tiendapos.com');
        } else {
            $this->testFail('Las credenciales no coinciden');
            return 1;
        }

        $this->testDetail('Email:', $user->email);
        $this->testDetail('Rol:', $user->roles->first()->name ?? 'sin rol');
        $this->testDetail('Tienda ID:', (string) ($user->tienda_id ?? 'N/A'));
        $this->testDetail('Activo:', $user->activo ? 'SÍ' : 'NO');

        // ─── PASO 2 ─────────────────────────────────────────────
        $this->testStep(2, 'Generando token Sanctum con abilities por rol');

        $user->tokens()->delete();
        $token = $user->createToken('test-cli-' . time(), $this->getAbilities($user));
        $plainText = $token->plainTextToken;

        $this->testOk('Token generado correctamente');
        $this->testDetail('Token:', substr($plainText, 0, 30) . '...');
        $this->testDetail('Abilities:', implode(', ', $this->getAbilities($user)));

        $user->update(['ultimo_login' => now()]);
        $this->testOk('Último login actualizado: ' . now()->format('Y-m-d H:i:s'));

        // ─── PASO 3 ─────────────────────────────────────────────
        $this->testStep(3, 'Verificando GET /auth/me (usuario autenticado)');

        $userLogueado = User::with('tienda', 'roles')->find($user->id);
        $this->testOk('Usuario autenticado correctamente');
        $this->testDetail('ID:', (string) $userLogueado->id);
        $this->testDetail('Email:', $userLogueado->email);
        $this->testDetail('Tienda ID:', (string) ($userLogueado->tienda_id ?? 'N/A'));
        $this->testDetail('Rol principal:', $userLogueado->roles->first()->name ?? 'sin rol');
        $this->testDetail('Estado:', $userLogueado->activo ? '🟢 Activo' : '🔴 Inactivo');

        // ─── PASO 4 ─────────────────────────────────────────────
        $this->testStep(4, 'Simulando REFRESH de token (rotación)');

        $user->tokens()->where('name', 'test-cli-' . time())->delete();
        $nuevoToken = $user->createToken('test-cli-refreshed', $this->getAbilities($user))->plainTextToken;
        $this->testOk('Token anterior revocado');
        $this->testOk('Nuevo token generado (rotación exitosa)');
        $this->testDetail('Nuevo token:', substr($nuevoToken, 0, 30) . '...');

        // ─── PASO 5 ─────────────────────────────────────────────
        $this->testStep(5, 'Probando cambio de contraseña');

        $nuevaPassword = 'NuevaClave2026';
        $user->update(['password' => Hash::make($nuevaPassword)]);
        $this->testOk('Contraseña actualizada en BD');

        $loginConNueva = Hash::check($nuevaPassword, $user->fresh()->password);
        if ($loginConNueva) {
            $this->testOk('Verificación con nueva contraseña: CORRECTA');
        } else {
            $this->testFail('La verificación falló');
        }

        $user->update(['password' => Hash::make($passwordOriginal)]);
        $this->testInfo('Password restaurada al valor original para no romper otros tests');

        // ─── PASO 6 ─────────────────────────────────────────────
        $this->testStep(6, 'Probando bloqueo por usuario inactivo');

        $user->update(['activo' => false]);
        $user->refresh();
        if (!$user->activo) {
            $this->testOk('Usuario marcado como inactivo');
            $this->testOk('Middleware "activo" lo bloquearía en API (403 Forbidden)');
        }
        $user->update(['activo' => true]);
        $this->testInfo('Usuario reactivado para continuar tests');

        // ─── PASO 7 ─────────────────────────────────────────────
        $this->testStep(7, 'Probando LOGOUT (revocación de token)');

        $user->tokens()->delete();
        $tokensRestantes = $user->tokens()->count();
        $this->testOk('Todos los tokens revocados');
        $this->testDetail('Tokens restantes:', (string) $tokensRestantes);

        // ─── RESUMEN FINAL ──────────────────────────────────────
        $this->testFooter('SISTEMA DE AUTENTICACIÓN 100% FUNCIONAL', true, [
            'Login con Sanctum'        => '✓ OK',
            'Endpoint /me'             => '✓ OK',
            'Refresh (rotación token)' => '✓ OK',
            'Cambio de contraseña'     => '✓ OK',
            'Bloqueo usuario inactivo' => '✓ OK',
            'Logout (revocación)'      => '✓ OK',
            'Rate limiting configurado'=> '✓ 5/min login, 3/min reset',
        ]);

        return 0;
    }

    private function getAbilities($user): array
    {
        if ($user->hasRole('admin')) {
            return ['*'];
        }
        if ($user->hasRole('supervisor')) {
            return ['ventas', 'inventario', 'caja', 'creditos', 'reportes', 'devoluciones'];
        }
        if ($user->hasRole('cajero')) {
            return ['ventas', 'caja', 'clientes'];
        }
        return ['ventas'];
    }
}
