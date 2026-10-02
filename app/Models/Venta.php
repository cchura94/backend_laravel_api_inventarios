<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function cliente(){
        return $this->belongsTo(Cliente::class);
    }

    public function almacen_producto_venta(){
        return $this->belongsToMany(Producto::class, "detalle_venta")
                    ->withPivot(["almacen_id", "cantidad", "precio_unitario_venta", "observaciones"])
                    ->withTimestamps();
    }
}
