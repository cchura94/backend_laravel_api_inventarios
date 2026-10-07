<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $fillable = ["nombre", "direccion", "telefono", "ciudad"];

    public function almacenes(){
        return $this->hasMany(Almacen::class);
    }

    public function users(){
        return $this->belongsToMany(User::class)
                    ->withPivot(["role_id"])
                    ->withTimestamps();
    }
}
