<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = ["nombre_completo", "nro_identificacion", "fecha_nacimiento", "telefono", "correo", "estado"];

    public function ventas(){
        return $this->hasMany(Venta::class);
    }
}
