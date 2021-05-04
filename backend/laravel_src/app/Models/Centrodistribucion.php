<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Centrodistribucion extends Model
{
    use HasFactory;
    protected $table = 'centrosdistribucion';

    protected $fillable = ['rut', 'name', 'address', 'city', 'country'];

    public function sucursales()
    {
        return $this->hasMany(Sucursal::class);
    }
}
