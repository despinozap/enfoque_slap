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

    public function oc()
    {
        return $this->belongsTo(Oc::class);
    }

    public function ocpartes()
    {
        return $this->belongsToMany(OcParte::class, 'entrega_ocparte', 'entrega_id', 'ocparte_id')->withPivot(['cantidad'])->using(OcParteEntrega::class)->withTimestamps();
    }

    public function getPartesTotalAttribute()
    {
        $quantity = $this->ocpartes->reduce(function($carry, $ocParte)
            {
                return $carry + $ocParte->pivot->cantidad; 
            }, 
            0
        );

        $this->attributes['partes_total'] = $quantity;
        
        return $quantity;
    }
}
