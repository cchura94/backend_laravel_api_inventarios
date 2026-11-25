<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['nombre', 'descripcion'];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function permisos()
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }
}
