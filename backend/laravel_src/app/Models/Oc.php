<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oc extends Model
{
    use HasFactory;

    protected $table = 'ocs';
    protected $fillable = [
        'cotizacion_id', 'proveedor_id', 'filedata_id', 'estadooc_id', 'noccliente', 'usdvalue',
    ];
    public $appends = ['partes_total'];

    public function setMontoAttribute($value)
    {
        $this->attributes['monto'] = $value;
    }

    public function getUsdMontoAttribute()
    {
        // Partes ID's
        $partesIds = $this->partes->reduce(function($carry, $cparte)
            { 
                array_push($carry, $cparte->id); 
            
                return $carry; 
            }, 
            // Initial empty array
            array()
        );
        
        // For all the Cotizacion partes match ID's with OC partes
        $amount = $this->cotizacion->partes->whereIn('id', $partesIds)->reduce(function($carry, $parte) 
            { 
                // Adds the monto field multiplied by cantidad
                return $carry += ($parte->pivot->monto * $parte->pivot->cantidad); 
            }, 
            // Initial value
            0
        );

        return $amount;
    }

    public function getPartesTotalAttribute()
    {
        $quantity = 0;

        foreach($this->partes as $parte)
        {
            $quantity += $parte->pivot->cantidad;
        }

        return $quantity;
    }
    
    public function partes()
    {
        return $this->belongsToMany(Parte::class, 'oc_parte', 'oc_id', 'parte_id')->withPivot(['descripcion', 'estadoocparte_id', 'cantidad', 'cantidadpendiente', 'cantidadasignado', 'cantidaddespachado', 'cantidadrecibido', 'cantidadentregado'])->using(OcParte::class);
    }

    public function estadooc()
    {
        return $this->belongsTo(Estadooc::class);
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }
    
    public function filedata()
    {
        // It returns the filedata which represents the OC Cliente document attached to the OC
        return $this->belongsTo(Filedata::class);
    }
}
