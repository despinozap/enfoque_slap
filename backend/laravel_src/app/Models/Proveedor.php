<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';
    protected $fillable = [
        'comprador_id', 'rut', 'name', 'address', 'city', 'contact', 'phone' 
    ];

    public function comprador()
    {
        return $this->belongsTo(Comprado::class);
    }
}
