<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categorias = DB::select("SELECT * FROM categorias");

        return response()->json($categorias);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $nombre = $request->nombre;
        $descripcion = $request->descripcion;
        DB::insert("insert into categorias(nombre, descripcion) values (?, ?)", [$nombre, $descripcion]);

        return response()->json(["message" => "Categoria registrada en la BD"]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $categoria = DB::select("select * from categorias where id = $id");

        return response()->json($categoria);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $nombre = $request->nombre;
        $descripcion = $request->descripcion;

        DB::update("update categorias set nombre='$nombre', descripcion='$descripcion' where id = $id");
        return response()->json(["message" => "Categoria actualizada en la BD"]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::delete("delete from categorias where id = $id");
        return response()->json(["message" => "Categoria Eliminado de la BD"]);

    }
}
