<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limite = $request->limit ?? 10;
        $buscar = $request->search;

        $clientes = Cliente::where(function ($query) use ($buscar) {
                                $query->where("nombre_completo", "iLIKE", "%$buscar%")
                                      ->orWhere("nro_identificacion", "iLIKE", "%$buscar%")
                                      ->orWhere("correo", "iLIKE", "%$buscar%")
                                      ->orWhere("telefono", "iLIKE", "%$buscar%");
                            })
                            ->orderBy("id", "DESC")
                            ->paginate($limite);

        return response()->json($clientes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            "nombre_completo" => "required|string|max:255",
            "nro_identificacion" => "nullable|string|max:30",
            "fecha_nacimiento" => "nullable|date",
            "telefono" => "nullable|string|max:22",
            "correo" => "nullable|email|max:200",
            "estado" => "nullable|boolean",
        ]);

        $cliente = Cliente::create($datos);

        return response()->json([
            "mensaje" => "Cliente registrado",
            "cliente" => $cliente,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cliente = Cliente::with("ventas")->findOrFail($id);

        return response()->json($cliente);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $cliente = Cliente::findOrFail($id);

        $datos = $request->validate([
            "nombre_completo" => "required|string|max:255",
            "nro_identificacion" => "nullable|string|max:30",
            "fecha_nacimiento" => "nullable|date",
            "telefono" => "nullable|string|max:22",
            "correo" => "nullable|email|max:200",
            "estado" => "nullable|boolean",
        ]);

        $cliente->update($datos);

        return response()->json([
            "mensaje" => "Cliente actualizado",
            "cliente" => $cliente,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return response()->json(["mensaje" => "Cliente eliminado"]);
    }
}
