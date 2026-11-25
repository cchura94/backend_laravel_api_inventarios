<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('usuario.ver');

        // Se usa Eloquent para incluir los roles y ocultar campos sensibles
        // (password / remember_token) definidos en el modelo User.
        return response()->json(
            User::with('roles')->orderBy('id', 'DESC')->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('usuario.crear');

        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
        ]);

        // Solo se asignan roles si el campo viene en la petición.
        if (array_key_exists('roles', $datos)) {
            $usuario->roles()->sync($datos['roles'] ?? []);
        }

        return response()->json([
            'mensaje' => 'Usuario Registrado',
            'usuario' => $usuario->load('roles'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('usuario.ver');

        return response()->json(
            User::with('roles')->findOrFail($id)
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->authorize('usuario.editar');

        $usuario = User::findOrFail($id);

        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario->id),
            ],
            'password' => 'nullable|string|min:6',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        // Evita dejar el sistema sin ningún administrador.
        if (array_key_exists('roles', $datos)
            && $this->seriaUltimoAdministrador($usuario, $datos['roles'] ?? [])) {
            return response()->json([
                'mensaje' => 'Debe existir al menos un usuario con el rol administrador.',
            ], 422);
        }

        $usuario->name = $datos['name'];
        $usuario->email = $datos['email'];

        // La contraseña solo se cambia si se envía un valor.
        if (! empty($datos['password'])) {
            $usuario->password = $datos['password'];
        }

        $usuario->save();

        if (array_key_exists('roles', $datos)) {
            $usuario->roles()->sync($datos['roles'] ?? []);
        }

        return response()->json([
            'mensaje' => 'Usuario Actualizado',
            'usuario' => $usuario->load('roles'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $this->authorize('usuario.eliminar');

        $usuario = User::findOrFail($id);

        if ($request->user()->id === $usuario->id) {
            return response()->json([
                'mensaje' => 'No puedes eliminar tu propio usuario.',
            ], 422);
        }

        if ($this->esUltimoAdministrador($usuario)) {
            return response()->json([
                'mensaje' => 'Debe existir al menos un usuario con el rol administrador.',
            ], 422);
        }

        // Las tablas pivote no tienen ON DELETE CASCADE.
        $usuario->roles()->detach();
        $usuario->sucursales()->detach();
        $usuario->tokens()->delete();
        $usuario->delete();

        return response()->json(['mensaje' => 'Usuario Eliminado']);
    }

    /**
     * @param  array<int, int|string>  $rolesNuevos
     */
    private function seriaUltimoAdministrador(User $usuario, array $rolesNuevos): bool
    {
        $rolAdministrador = Role::where('nombre', User::ROL_ADMINISTRADOR)->value('id');

        if (! $rolAdministrador || ! $usuario->esAdministrador()) {
            return false;
        }

        $rolesNuevos = array_map('intval', $rolesNuevos);

        if (in_array((int) $rolAdministrador, $rolesNuevos, true)) {
            return false;
        }

        return $this->cantidadAdministradores() <= 1;
    }

    private function esUltimoAdministrador(User $usuario): bool
    {
        return $usuario->esAdministrador() && $this->cantidadAdministradores() <= 1;
    }

    private function cantidadAdministradores(): int
    {
        return User::whereHas(
            'roles',
            fn ($query) => $query->where('nombre', User::ROL_ADMINISTRADOR)
        )->count();
    }
}
