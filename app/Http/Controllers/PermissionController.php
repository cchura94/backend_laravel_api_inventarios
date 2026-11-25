<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('permiso.ver');

        $limite = $request->limit ?? 10;
        $buscar = $request->search;

        $permisos = Permission::where(function ($query) use ($buscar) {
            $query->whereRaw('lower(nombre) like lower(?)', ["%$buscar%"])
                ->orWhereRaw('lower(descripcion) like lower(?)', ["%$buscar%"]);
        })
            ->withCount('roles')
            ->orderBy('id', 'DESC')
            ->paginate($limite);

        return response()->json($permisos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('permiso.crear');

        $datos = $request->validate([
            'nombre' => 'required|string|max:255|unique:permissions,nombre',
            'descripcion' => 'nullable|string',
        ]);

        $permiso = Permission::create($datos);

        return response()->json([
            'mensaje' => 'Permiso registrado',
            'permiso' => $permiso,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permiso)
    {
        $this->authorize('permiso.ver');

        return response()->json($permiso->load('roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permiso)
    {
        $this->authorize('permiso.editar');

        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'nombre')->ignore($permiso->id),
            ],
            'descripcion' => 'nullable|string',
        ]);

        $permiso->update($datos);

        return response()->json([
            'mensaje' => 'Permiso actualizado',
            'permiso' => $permiso,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permiso)
    {
        $this->authorize('permiso.eliminar');

        // la tabla pivote no tiene ON DELETE CASCADE: se limpia antes de borrar
        $permiso->roles()->detach();
        $permiso->delete();

        return response()->json(['mensaje' => 'Permiso eliminado']);
    }
}
