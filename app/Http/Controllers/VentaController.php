<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ventas = Venta::with(['user', 'cliente', 'almacen_producto_venta'])->orderBy('id', 'DESC')->paginate(10);

        return response()->json($ventas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            "cliente_id" => "required|exists:clientes,id",
            "descuento_total" => "nullable",
            "detalle" => "nullable",
            "observaciones" => "nullable",
            "movimientos.*.almacen_id" => "required|exists:almacens,id",
            "movimientos.*.producto_id" => "required|exists:productos,id",
            "movimientos.*.cantidad" => "required|integer|min:1",
            "movimientos.*.precio_unitario_venta" => "required|numeric",
        ]);

        try {
            // Transacciones
            DB::beginTransaction();
            // Creación de una Nota de Venta en la BD
            $venta = new Venta();
            $venta->codigo = "";
            $venta->fecha = date('Y-m-d H:i:s');
            $venta->detalle = $request->detalle;
            $venta->descuento_total = $request->descuento_total;
            $venta->observaciones = $request->observaciones;
            $venta->user_id = $request->user()->id;
            $venta->cliente_id = $request->cliente_id;
            $venta->estado = "EN PROCESO";
            $venta->save();

            $venta->codigo = 'VENT-'.str_pad($venta->id, 6, '0', STR_PAD_LEFT);
            $venta->save();

            // Registrar Movimientos en la BD
            // actualización de stock de productos por almacen (disminuye)
            foreach ($request->movimientos as $mov) {
                // verificamos stock disponible
                $pivot = DB::table('almacen_producto')
                                ->where('almacen_id', $mov['almacen_id'])
                                ->where('producto_id', $mov['producto_id'])
                                ->first();

                if (!$pivot || $pivot->cantidad_actual < $mov['cantidad']) {
                    $disponible = $pivot ? $pivot->cantidad_actual : 0;
                    throw new \Exception("Stock insuficiente para el producto id {$mov['producto_id']} en el almacen id {$mov['almacen_id']}. Disponible: {$disponible}");
                }

                $venta->almacen_producto_venta()->attach($mov['producto_id'], [
                    'almacen_id' => $mov['almacen_id'],
                    'cantidad' => $mov['cantidad'],
                    'precio_unitario_venta' => $mov['precio_unitario_venta'],
                    'observaciones' => $mov['observaciones'] ?? null,
                ]);

                // actualizamos el stock
                $nuevaCantidad = $pivot->cantidad_actual - $mov['cantidad'];
                DB::table('almacen_producto')
                            ->where('almacen_id', $mov['almacen_id'])
                            ->where('producto_id', $mov['producto_id'])
                            ->update([
                                "cantidad_actual" => $nuevaCantidad,
                                "fecha_actualizacion" => date('Y-m-d H:i:s')
                            ]);
            }

            // Registrar nota venta
            $venta->estado = "COMPLETADO";
            $venta->update();

            DB::commit();
            return response()->json(["mensaje" => "Nota de Venta Creada correctamente"], 201);
        } catch (\Exception $e) {
            //revierte todo;
            DB::rollBack();
            return response()->json(["mensaje" => "Error al registrar la Nota de Venta", "error" => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $venta = Venta::with(['user', 'cliente', 'almacen_producto_venta'])->findOrFail($id);

        return response()->json($venta);
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

    public function generarReciboPDF($id){

        $data = Venta::with(['user', 'cliente', 'almacen_producto_venta'])->find($id);

        $pdf = Pdf::loadView('pdf.recibo', ["data" => $data]);
        return $pdf->download("$data->codigo-recibo.pdf");
    }
}
