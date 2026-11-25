<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['nombre', 'descripcion'];

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }
}
