<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('rol.ver');

        $limite = $request->limit ?? 10;
        $buscar = $request->search;

        $roles = Role::where(function ($query) use ($buscar) {
            $query->whereRaw('lower(nombre) like lower(?)', ["%$buscar%"])
                ->orWhereRaw('lower(descripcion) like lower(?)', ["%$buscar%"]);
        })
            ->with(['permisos'])
            ->withCount('users')
            ->orderBy('id', 'DESC')
            ->paginate($limite);

        return response()->json($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('rol.crear');

        $datos = $request->validate([
            // roles.nombre es varchar(50) y único
            'nombre' => 'required|string|max:50|unique:roles,nombre',
            'descripcion' => 'nullable|string',
            'permisos' => 'nullable|array',
            'permisos.*' => 'integer|exists:permissions,id',
        ]);

        $rol = Role::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
        ]);

        // si envían permisos, se asignan al rol
        if (array_key_exists('permisos', $datos)) {
            $rol->permisos()->sync($datos['permisos'] ?? []);
        }

        return response()->json([
            'mensaje' => 'Rol registrado',
            'rol' => $rol->load('permisos'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $rol)
    {
        $this->authorize('rol.ver');

        return response()->json(
            $rol->load('permisos')->loadCount('users')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $rol)
    {
        $this->authorize('rol.editar');

        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:50',
                Rule::unique('roles', 'nombre')->ignore($rol->id),
            ],
            'descripcion' => 'nullable|string',
            'permisos' => 'nullable|array',
            'permisos.*' => 'integer|exists:permissions,id',
        ]);

        $rol->update([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
        ]);

        // solo se reemplazan los permisos si el campo viene en la petición
        if (array_key_exists('permisos', $datos)) {
            $rol->permisos()->sync($datos['permisos'] ?? []);
        }

        return response()->json([
            'mensaje' => 'Rol actualizado',
            'rol' => $rol->load('permisos'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $rol)
    {
        $this->authorize('rol.eliminar');

        // las tablas pivote no tienen ON DELETE CASCADE: se limpian antes de borrar
        $rol->permisos()->detach();
        $rol->users()->detach();
        $rol->delete();

        return response()->json(['mensaje' => 'Rol eliminado']);
    }

    /**
     * Reemplaza por completo los permisos de un rol.
     */
    public function actualizarPermisos(Request $request, Role $rol)
    {
        $this->authorize('rol.editar');

        $datos = $request->validate([
            'permisos' => 'present|array',
            'permisos.*' => 'integer|exists:permissions,id',
        ]);

        $rol->permisos()->sync($datos['permisos']);

        return response()->json([
            'mensaje' => 'Permisos del rol actualizados',
            'rol' => $rol->load('permisos'),
        ]);
    }

    /**
     * Asigna un permiso al rol sin quitar los que ya tiene.
     */
    public function asignarPermiso(Role $rol, Permission $permiso)
    {
        $this->authorize('rol.editar');

        $rol->permisos()->syncWithoutDetaching([$permiso->id]);

        return response()->json([
            'mensaje' => 'Permiso asignado al rol',
            'rol' => $rol->load('permisos'),
        ]);
    }

    /**
     * Quita un permiso del rol.
     */
    public function quitarPermiso(Role $rol, Permission $permiso)
    {
        $this->authorize('rol.editar');

        $rol->permisos()->detach($permiso->id);

        return response()->json([
            'mensaje' => 'Permiso quitado del rol',
            'rol' => $rol->load('permisos'),
        ]);
    }
}
