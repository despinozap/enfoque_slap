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

    public function oc()
    {
        return $this->belongsTo(Oc::class);
    }

    public function ocpartes()
    {
        return $this->belongsToMany(OcParte::class, 'recepcion_ocparte', 'recepcion_id', 'ocparte_id')->withPivot(['cantidad'])->withTimestamps();
    }

    public function getPartesTotalAttribute()
    {
        $quantity = $this->ocpartes->reduce(function($carry, $ocparte)
            {
                return $carry + $ocparte->pivot->cantidad; 
            }, 
            0
        );

        $this->attributes['partes_total'] = $quantity;
        
        return $quantity;
    }
}
