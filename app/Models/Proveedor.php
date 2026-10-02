<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    public function compras(){
        return $this->hasMany(Compra::class);
    }
}
