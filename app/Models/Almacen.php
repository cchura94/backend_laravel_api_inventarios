<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    public function productos(){
        return $this->belongsToMany(Producto::class)
                ->withPivot(["cantidad_actual", "fecha_actualizacion"])
                ->withTimestamps(); //created_at, updated_at
    }

    public function sucursal(){
        return $this->belongsTo(Sucursal::class);
    }
}
