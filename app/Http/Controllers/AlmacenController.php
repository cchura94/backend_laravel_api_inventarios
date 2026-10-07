<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limite = $request->limit ?? 10;
        $buscar = $request->search;

        $almacenes = Almacen::with("sucursal")
                            ->where(function ($query) use ($buscar) {
                                $query->where("nombre", "iLIKE", "%$buscar%")
                                      ->orWhere("codigo", "iLIKE", "%$buscar%");
                            })
                            ->when($request->sucursal_id, function ($query, $sucursalId) {
                                $query->where("sucursal_id", $sucursalId);
                            })
                            ->orderBy("id", "DESC")
                            ->paginate($limite);

        return response()->json($almacenes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            "nombre" => "required|string|max:255",
            "codigo" => "nullable|string|max:255",
            "descripcion" => "nullable|string",
            "sucursal_id" => "required|integer|exists:sucursals,id",
        ]);

        $almacen = Almacen::create($datos);

        return response()->json([
            "mensaje" => "Almacen registrado",
            "almacen" => $almacen->load("sucursal"),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $almacen = Almacen::with(["sucursal", "productos"])->findOrFail($id);

        return response()->json($almacen);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $almacen = Almacen::findOrFail($id);

        $datos = $request->validate([
            "nombre" => "required|string|max:255",
            "codigo" => "nullable|string|max:255",
            "descripcion" => "nullable|string",
            "sucursal_id" => "required|integer|exists:sucursals,id",
        ]);

        $almacen->update($datos);

        return response()->json([
            "mensaje" => "Almacen actualizado",
            "almacen" => $almacen->load("sucursal"),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $almacen = Almacen::findOrFail($id);

        if ($almacen->productos()->exists()) {
            return response()->json([
                "mensaje" => "No se puede eliminar el almacen porque tiene productos asociados",
            ], 409);
        }

        $almacen->delete();

        return response()->json(["mensaje" => "Almacen eliminado"]);
    }
}
