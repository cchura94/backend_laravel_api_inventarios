<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $fillable = ["razon_social", "nro_identificacion", "contacto", "telefono", "correo", "observaciones", "estado"];

    public function compras(){
        return $this->hasMany(Compra::class);
    }
}
