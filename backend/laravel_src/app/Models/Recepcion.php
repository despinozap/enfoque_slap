<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recepcion extends Model
{
    use HasFactory;

    protected $table = 'recepciones';
    public $appends = ['partes_total'];

    /*
     *  The Recepcion's source.
     *  Sourceable models: Proveedor, Comprador, Centrodistribucion
     */
    public function sourceable()
    {
        return $this->morphTo();
    }

    /*
     * The Recepcion's destination. 
     * Recepcionable models: Comprador, Centrodistribucion, Sucursal
     */
    public function recepcionable()
    {
        return $this->morphTo();
    }

    public function partes()
    {
        return $this->belongsToMany(Parte::class, 'parte_recepcion', 'recepcion_id', 'parte_id')->withPivot(['cantidad'])->using(ParteRecepcion::class)->withTimestamps();
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
