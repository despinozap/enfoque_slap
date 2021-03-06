<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes';
    protected $fillable = [
        'sucursal_id', 'faena_id', 'marca_id', 'comprador_id', 'user_id', 'estadosolicitud_id', 'comentario', 
    ];
    protected $appends = ['partes_total'];

    public function getPartesTotalAttribute()
    {
        $quantity = 0;

        foreach($this->partes as $parte)
        {
            $quantity += $parte->pivot->cantidad;
        }

        return $quantity;
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function faena()
    {
        return $this->belongsTo(Faena::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function comprador()
    {
        return $this->belongsTo(Comprador::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function estadosolicitud()
    {
        return $this->belongsTo(Estadosolicitud::class);
    }

    public function partes()
    {
        return $this->belongsToMany(Parte::class, 'parte_solicitud')->withPivot('cantidad', 'descripcion', 'costo', 'margen', 'tiempoentrega', 'peso', 'flete', 'monto', 'backorder');
    }
}