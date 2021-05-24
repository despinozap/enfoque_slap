<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Despacho extends Model
{
    use HasFactory;

    protected $table = 'despachos';
    public $appends = ['partes_total'];

    /*
     * The Despacho's source. 
     * Despachable models: Comprador, Centrodistribucion, Sucursal
     */
    public function despachable()
    {
        return $this->morphTo();
    }

    /*
     *  The Despacho's destination.
     *  Destinable models: Centrodistribucion, Sucursal, Faena
     */
    public function destinable()
    {
        return $this->morphTo();
    }

    public function partes()
    {
        return $this->belongsToMany(Parte::class, 'despacho_parte', 'despacho_id', 'parte_id')->withPivot(['cantidad'])->using(ParteDespacho::class)->withTimestamps();
    }

    public function getPartesTotalAttribute()
    {
        $quantity = $this->partes->reduce(function($carry, $parte)
            {
                return $carry + $parte->pivot->cantidad; 
            }, 
            0
        );

        $this->attributes['partes_total'] = $quantity;
        
        return $quantity;
    }

    
}
