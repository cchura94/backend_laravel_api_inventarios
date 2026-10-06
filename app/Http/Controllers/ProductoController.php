<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limite = $request->limit;
        $buscar = $request->search;

        $productos= Producto::where("nombre", "iLIKE", "%$buscar%")
                                ->orWhere("codigo_barra", "iLike", "%$buscar%")
                                ->orderBy('id', 'DESC')
                                ->paginate($limite);

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


        if($file = $request->file("imagen")){
            $direccion_url = time()."-".$file->getClientOriginalName();
            $file->move("imagenes", $direccion_url);

            $prod->imagen = "imagenes/".$direccion_url;

        }


        $prod->save();

        // responder
        return response()->json(["mensaje" => "Producto registrado"]);
    }

    public function actualizarImagen(Request $request, $id){

        $request->validate(
            [
                "imagen" => "required|image|mimes:jpeg,png,jpg,gif|max:2048"
            ]
        );

        if($file = $request->file("imagen")){
            $direccion_url = time()."-".$file->getClientOriginalName();
            $file->move("imagenes", $direccion_url);

            $producto = Producto::find($id);
            $producto->imagen = "imagenes/".$direccion_url;
            $producto->update();

            return response()->json($producto);
        }
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
