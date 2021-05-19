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

    public function ocpartes()
    {
        return $this->belongsToMany(OcParte::class, 'despacho_ocparte', 'despacho_id', 'ocparte_id')->withPivot(['cantidad'])->using(OcParteDespacho::class)->withTimestamps();
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
