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

    public function ocpartes()
    {
        return $this->belongsToMany(OcParte::class, 'ocparte_recepcion', 'recepcion_id', 'ocparte_id')->withPivot(['cantidad'])->using(OcParteRecepcion::class)->withTimestamps();
    }

    public function getPartesTotalAttribute()
    {
        $quantity = 0;

        foreach($this->ocpartes as $ocparte)
        {
            $quantity += $ocparte->pivot->cantidad;
        }

        $this->attributes['partes_total'] = $quantity;
        
        return $quantity;
    }
}
