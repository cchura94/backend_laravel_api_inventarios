<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $productos= Producto::get();
        return response($productos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validar
        $request->validate([
            "nombre" => "required",
            "descripcion" => "nullable",
            "codigo_barra" => "nullable",
            "unidad_medida" => "nullable",
            "marca" => "nullable",
            "categoria_id" => "required",
            "precio_venta_actual" => "required",
            "stock_minimo" => "nullable",
            "imagen" => "nullable",
            "estado" => "required",
            "fecha_registro" => "nullable"
        ]);


        // guardar
        $prod = new Producto();
        $prod->nombre = $request->nombre;
        $prod->descripcion = $request->descripcion;
        $prod->codigo_barra = $request->codigo_barra;
        $prod->unidad_medida = $request->unidad_medida;
        $prod->marca = $request->marca;
        $prod->categoria_id = $request->categoria_id;
        $prod->precio_venta_actual = $request->precio_venta_actual;
        $prod->stock_minimo = $request->stock_minimo;
        // $prod->imagen = $request->imagen;
        $prod->estado = $request->estado;
        $prod->fecha_registro = $request->fecha_registro;
        $prod->save();

        // responder
        return response()->json(["mensaje" => "Producto registrado"]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
