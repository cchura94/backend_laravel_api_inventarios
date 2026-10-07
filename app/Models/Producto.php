<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        "nombre", "descripcion", "codigo_barra", "unidad_medida", "marca",
        "categoria_id", "precio_venta_actual", "stock_minimo", "imagen",
        "estado", "fecha_registro",
    ];

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
