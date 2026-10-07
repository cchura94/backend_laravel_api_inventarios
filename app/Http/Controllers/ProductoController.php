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
        $limite = $request->limit ?? 10;
        $buscar = $request->search;

        $productos = Producto::with("categoria")
            ->where(function ($query) use ($buscar) {
                $query->where("nombre", "iLIKE", "%$buscar%")
                      ->orWhere("codigo_barra", "iLIKE", "%$buscar%");
            })
            ->orderBy('id', 'DESC')
            ->paginate($limite);

        return response()->json($productos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validar
        $datos = $request->validate([
            "nombre" => "required|string|max:200",
            "descripcion" => "nullable|string",
            "codigo_barra" => "nullable|string|max:100",
            "unidad_medida" => "nullable|string|max:50",
            "marca" => "nullable|string|max:100",
            "categoria_id" => "required|integer|exists:categorias,id",
            "precio_venta_actual" => "required|numeric|min:0",
            "stock_minimo" => "nullable|integer|min:0",
            "imagen" => "nullable|image|mimes:jpeg,png,jpg,gif|max:2048",
            "estado" => "required|boolean",
            "fecha_registro" => "nullable|date",
        ]);

        $prod = new Producto();
        $prod->nombre = $datos["nombre"];
        $prod->descripcion = $datos["descripcion"] ?? null;
        $prod->codigo_barra = $datos["codigo_barra"] ?? null;
        $prod->unidad_medida = $datos["unidad_medida"] ?? null;
        $prod->marca = $datos["marca"] ?? null;
        $prod->categoria_id = $datos["categoria_id"];
        $prod->precio_venta_actual = $datos["precio_venta_actual"];
        $prod->stock_minimo = $datos["stock_minimo"] ?? 1;
        $prod->estado = $datos["estado"];
        $prod->fecha_registro = $datos["fecha_registro"] ?? now();

        if ($file = $request->file("imagen")) {
            $direccion_url = time()."-".$file->getClientOriginalName();
            $file->move("imagenes", $direccion_url);

            $prod->imagen = "imagenes/".$direccion_url;
        }

        $prod->save();

        // responder
        return response()->json([
            "mensaje" => "Producto registrado",
            "producto" => $prod->load("categoria"),
        ], 201);
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
        $producto = Producto::with(["categoria", "almacenes"])->findOrFail($id);

        return response()->json($producto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $producto = Producto::findOrFail($id);

        $datos = $request->validate([
            "nombre" => "required|string|max:200",
            "descripcion" => "nullable|string",
            "codigo_barra" => "nullable|string|max:100",
            "unidad_medida" => "nullable|string|max:50",
            "marca" => "nullable|string|max:100",
            "categoria_id" => "required|integer|exists:categorias,id",
            "precio_venta_actual" => "required|numeric|min:0",
            "stock_minimo" => "nullable|integer|min:0",
            "imagen" => "nullable|image|mimes:jpeg,png,jpg,gif|max:2048",
            "estado" => "required|boolean",
            "fecha_registro" => "nullable|date",
        ]);

        $producto->nombre = $datos["nombre"];
        $producto->descripcion = $datos["descripcion"] ?? null;
        $producto->codigo_barra = $datos["codigo_barra"] ?? null;
        $producto->unidad_medida = $datos["unidad_medida"] ?? null;
        $producto->marca = $datos["marca"] ?? null;
        $producto->categoria_id = $datos["categoria_id"];
        $producto->precio_venta_actual = $datos["precio_venta_actual"];
        $producto->stock_minimo = $datos["stock_minimo"] ?? 1;
        $producto->estado = $datos["estado"];

        if (!empty($datos["fecha_registro"])) {
            $producto->fecha_registro = $datos["fecha_registro"];
        }

        if ($file = $request->file("imagen")) {
            $direccion_url = time()."-".$file->getClientOriginalName();
            $file->move("imagenes", $direccion_url);

            $producto->imagen = "imagenes/".$direccion_url;
        }

        $producto->save();

        return response()->json([
            "mensaje" => "Producto actualizado",
            "producto" => $producto->load("categoria"),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $producto = Producto::findOrFail($id);

        if ($producto->almacenes()->exists()) {
            return response()->json([
                "mensaje" => "No se puede eliminar el producto porque tiene stock en almacenes",
            ], 409);
        }

        $producto->delete();

        return response()->json(["mensaje" => "Producto eliminado"]);
    }
}
