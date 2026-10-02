<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    // conecta con la tabla llamada categorias
    // protected $table = "wp_categoria";
    // protected $primaryKey = 'id';

    // protected $primaryKey = 'categoria_id';
    // public $incrementing = false;
    // protected $keyType = 'string';
    public function productos(){
        return $this->hasMany(Producto::class);
    }

}
