<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrega extends Model
{
    use HasFactory;

    protected $table = 'entregas';
    public $appends = ['partes_total'];

    /*
     * The Entrega's source. 
     * Despachable models: Centrodistribucion, Sucursal
     */
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    /*
     *  The Entrega's destination.
     *  Destinable models: Faena
     */
    public function faena()
    {
        return $this->belongsTo(Faena::class);
    }

    public function partes()
    {
        return $this->belongsToMany(OcParte::class, 'entrega_ocparte', 'entrega_id', 'ocparte_id')->withPivot(['cantidad'])->using(OcParteEntrega::class)->withTimestamps();
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
