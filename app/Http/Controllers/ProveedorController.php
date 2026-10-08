<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limite = $request->limit ?? 10;
        $buscar = $request->search;

        $proveedores = Proveedor::where(function ($query) use ($buscar) {
                                $query->where("razon_social", "iLIKE", "%$buscar%")
                                      ->orWhere("nro_identificacion", "iLIKE", "%$buscar%")
                                      ->orWhere("contacto", "iLIKE", "%$buscar%")
                                      ->orWhere("correo", "iLIKE", "%$buscar%");
                            })
                            ->orderBy("id", "DESC")
                            ->paginate($limite);

        return response()->json($proveedores);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            "razon_social" => "required|string|max:255",
            "nro_identificacion" => "nullable|string|max:30",
            "contacto" => "nullable|string|max:100",
            "telefono" => "nullable|string|max:22",
            "correo" => "nullable|email|max:200",
            "observaciones" => "nullable|string",
            "estado" => "nullable|boolean",
        ]);

        $proveedor = Proveedor::create($datos);

        return response()->json([
            "mensaje" => "Proveedor registrado",
            "proveedor" => $proveedor,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $proveedor = Proveedor::with("compras")->findOrFail($id);

        return response()->json($proveedor);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $datos = $request->validate([
            "razon_social" => "required|string|max:255",
            "nro_identificacion" => "nullable|string|max:30",
            "contacto" => "nullable|string|max:100",
            "telefono" => "nullable|string|max:22",
            "correo" => "nullable|email|max:200",
            "observaciones" => "nullable|string",
            "estado" => "nullable|boolean",
        ]);

        $proveedor->update($datos);

        return response()->json([
            "mensaje" => "Proveedor actualizado",
            "proveedor" => $proveedor,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->delete();

        return response()->json(["mensaje" => "Proveedor eliminado"]);
    }
}
