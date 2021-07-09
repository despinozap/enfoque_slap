<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Despacho extends Model
{
    use HasFactory;

    protected $table = 'despachos';
    protected $appends = ['partes_total'];
    protected $fillable = ['fecha', 'ndocumento', 'responsable', 'comentario'];

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
     *  Destinable models: Centrodistribucion, Sucursal
     */
    public function destinable()
    {
        return $this->morphTo();
    }

    public function ocpartes()
    {
        return $this->belongsToMany(OcParte::class, 'despacho_ocparte', 'despacho_id', 'ocparte_id')->withPivot(['cantidad'])->withTimestamps();
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
