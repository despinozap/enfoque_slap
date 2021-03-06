<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faena extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 'sucursal_id', 'rut', 'name', 'address', 'city', 'contact', 'phone' 
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }
}