<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $compras = Compra::with(['user', 'proveedor','almacen_producto_compra'])->paginate(10);

        return response()->json($compras);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            // "codigo" => "nullable",
            // "fecha" => "required",
            "proveedor_id" => "required|exists:proveedors,id",
            // "user_id" =>"required",
            "descuento_total" => "nullable",
            "detalle"=> "nullable",
            "observaciones"=> "nullable",
            "movimientos.*.almacen_id" => "required|exists:almacens,id",
            "movimientos.*.producto_id" => "required|exists:productos,id",
            "movimientos.*.cantidad" => "required|integer|min:1",
            "movimientos.*.precio_unitario_compra" => "required|numeric"

        ]);

        try {
            // Transacciones
            DB::beginTransaction();            
            // Creación de una Nota de Compra en la BD
            $compra = new Compra();
            $compra->codigo = "";
            $compra->fecha = date('Y-m-d H:i:s');
            $compra->detalle = $request->detalle;
            $compra->observaciones = $request->observaciones;
            $compra->user_id = $request->user()->id;
            $compra->proveedor_id = $request->proveedor_id;
            $compra->estado = "EN PROCESO";
            $compra->save();

            // Registrar Movimientos en la BD
            // actualización de stock de productos por almacen
            foreach ($request->movimientos as $mov) {
                $compra->almacen_producto_compra()->attach($mov['producto_id'], [
                    'almacen_id' => $mov['almacen_id'],
                    'cantidad' => $mov['cantidad'],
                    'precio_unitario_compra' => $mov['precio_unitario_compra'],
                    'observaciones' => $mov['observaciones']
                ]);

                // actualizamos el stock
                $pivot = DB::table('almacen_producto')
                                ->where('almacen_id', $mov['almacen_id'])
                                ->where('producto_id', $mov['producto_id'])
                                ->first();
                if(!$pivot){
                    DB::table('almacen_producto')->insert([
                        "almacen_id" => $mov['almacen_id'],
                        "producto_id" => $mov['producto_id'],
                        "cantidad_actual" => $mov['cantidad'],
                        "fecha_actualizacion" => date('Y-m-d H:i:s')
                    ]);
                }else{
                    $nuevaCantidad = $pivot->cantidad_actual;

                    $nuevaCantidad = $nuevaCantidad +  $mov['cantidad'];
                    DB::table('almacen_producto')
                                ->where('almacen_id', $mov['almacen_id'])
                                ->where('producto_id', $mov['producto_id'])
                                ->update([
                                    "cantidad_actual" => $nuevaCantidad,
                                    "fecha_actualizacion" => date('Y-m-d H:i:s')
                                ]);
                }
            }
            // Registra todo en la BD
            // Registrar nota compra
            $compra->estado = "COMPLETADO";
            $compra->update();

            DB::commit();
            return response()->json(["mensaje" => "Nota Creada correctamente"], 201);
            // Todo Bien
        } catch (\Exception $e) {
            //revierte todo;
            // Si existe un error (Revertir todo)
            DB::rollBack();
            return response()->json(["mensaje" => "Error al registrar la Nota de Compra", "error"=> $e->getMessage()], 500);
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
