<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    public function categoria(){
        return $this->belongsTo(Categoria::class);
    }

    public function almacenes(){
        return $this->belongsToMany(Almacen::class)
                ->withPivot(["cantidad_actual", "fecha_actualizacion"])
                ->withTimestamps(); //created_at, updated_at
    }

    public function almacen_producto_venta(){
        return $this->belongsToMany(Venta::class)
                    ->withPivot(["almacen_id", "cantidad", "precio_unitario_venta", "observaciones"]);
    }
}
