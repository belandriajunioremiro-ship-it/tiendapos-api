<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * @OA\Tag(name="Usuarios", description="Gestión de usuarios del sistema")
 */
class UsuarioController extends Controller
{
    public function __construct(private AuditoriaService $auditoria) {}
    /**
     * @OA\Get(
     *     path="/usuarios",
     *     summary="Listar usuarios",
     *     tags={"Usuarios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista de usuarios con roles")
     * )
     */
    public function index()
    {
        $usuarios = User::with('roles')->orderBy('name')->get();
        return response()->json(['data' => $usuarios]);
    }

    /**
     * @OA\Post(
     *     path="/usuarios",
     *     summary="Crear usuario",
     *     tags={"Usuarios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="name", type="string"), @OA\Property(property="email", type="string"), @OA\Property(property="password", type="string"), @OA\Property(property="password_confirmation", type="string"), @OA\Property(property="rol", type="string"), @OA\Property(property="activo", type="boolean"))),
     *     @OA\Response(response=201, description="Usuario creado")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'rol'      => 'required|string|exists:roles,name',
            'activo'   => 'sometimes|boolean',
        ]);

        $usuario = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'activo'   => $validated['activo'] ?? true,
        ]);

        $usuario->assignRole($validated['rol']);

        $this->auditoria->registrar('crear_usuario', 'users', $usuario->id, null, [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'rol' => $validated['rol'],
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data'    => $usuario->load('roles'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/usuarios/{id}",
     *     summary="Mostrar usuario",
     *     tags={"Usuarios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Usuario con roles")
     * )
     */
    public function show($id)
    {
        $usuario = User::with('roles')->findOrFail($id);
        return response()->json(['data' => $usuario]);
    }

    /**
     * @OA\Put(
     *     path="/usuarios/{id}",
     *     summary="Actualizar usuario",
     *     tags={"Usuarios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="name", type="string"), @OA\Property(property="email", type="string"), @OA\Property(property="password", type="string"), @OA\Property(property="rol", type="string"), @OA\Property(property="activo", type="boolean"))),
     *     @OA\Response(response=200, description="Usuario actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8|confirmed',
            'rol'      => 'sometimes|string|exists:roles,name',
            'activo'   => 'sometimes|boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        if (isset($validated['rol'])) {
            $usuario->syncRoles([$validated['rol']]);
            unset($validated['rol']);
        }

        $usuario->update($validated);

        return response()->json([
            'message' => 'Usuario actualizado.',
            'data'    => $usuario->fresh()->load('roles'),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/usuarios/{id}",
     *     summary="Desactivar usuario",
     *     tags={"Usuarios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Usuario desactivado"),
     *     @OA\Response(response=403, description="No puedes desactivar tu propio usuario")
     * )
     */
    public function destroy($id)
    {
        $usuario = User::findOrFail($id);

        // No permitir auto-desactivación
        if ($usuario->id === auth()->id()) {
            return response()->json(['message' => 'No puedes desactivar tu propio usuario.'], 403);
        }

        $snapshot = AuditoriaService::snapshot($usuario, ['id', 'name', 'email', 'activo']);
        $usuario->update(['activo' => false]);

        $this->auditoria->registrar('desactivar_usuario', 'users', $usuario->id, $snapshot, ['activo' => false]);

        return response()->json(['message' => 'Usuario desactivado.']);
    }
}