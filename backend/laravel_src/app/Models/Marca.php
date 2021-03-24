<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function partes()
    {
        return $this->hasMany(Parte::class);
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }
}
