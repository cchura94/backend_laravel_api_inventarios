<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('sucursal.ver');

        $limite = $request->limit ?? 10;
        $buscar = $request->search;

        $sucursales = Sucursal::where(function ($query) use ($buscar) {
            $query->where('nombre', 'iLIKE', "%$buscar%")
                ->orWhere('ciudad', 'iLIKE', "%$buscar%");
        })
            ->with(['almacenes'])
            ->orderBy('id', 'DESC')
            ->paginate($limite);

        return response()->json($sucursales);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('sucursal.crear');

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:22',
            'ciudad' => 'required|string|max:200',
        ]);

        $sucursal = Sucursal::create($datos);

        return response()->json([
            'mensaje' => 'Sucursal registrada',
            'sucursal' => $sucursal,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('sucursal.ver');

        $sucursal = Sucursal::with('almacenes')->findOrFail($id);

        return response()->json($sucursal);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->authorize('sucursal.editar');

        $sucursal = Sucursal::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:22',
            'ciudad' => 'required|string|max:200',
        ]);

        $sucursal->update($datos);

        return response()->json([
            'mensaje' => 'Sucursal actualizada',
            'sucursal' => $sucursal,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('sucursal.eliminar');

        $sucursal = Sucursal::findOrFail($id);
        $sucursal->delete();

        return response()->json(['mensaje' => 'Sucursal eliminada']);
    }
}
