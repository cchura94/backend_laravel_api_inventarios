<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
     public function user(){
        return $this->belongsTo(User::class);
    }

    public function proveedor(){
        return $this->belongsTo(Proveedor::class);
    }

    public function almacen_producto_compra(){
        return $this->belongsToMany(Producto::class, "detalle_compra")
                    ->withPivot(["almacen_id", "cantidad", "precio_unitario_compra", "observaciones"])
                    ->withTimestamps();
    }


}
