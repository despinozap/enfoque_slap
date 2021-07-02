<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parte extends Model
{
    use HasFactory;

    protected $fillable = [
        'marca_id', 'nparte' 
    ];

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function solicitudes()
    {
        return $this->belongsToMany(Solicitud::class, 'parte_solicitud');
    }

    public function cotizaciones()
    {
        return $this->belongsToMany(Cotizacion::class, 'cotizacion_parte');
    }

    public function ocs()
    {
        return $this->belongsToMany(Oc::class, 'oc_parte');
    }
}
