<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use DateTime;

class Oc extends Model
{
    use HasFactory;

    protected $table = 'ocs';
    protected $fillable = [
        'cotizacion_id', 'proveedor_id', 'filedata_id', 'estadooc_id', 'noccliente', 'usdvalue',
    ];
    public $appends = ['partes_total', 'dias'];

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
        $amount = $this->cotizacion->partes->whereIn('partes.id', $partesIds)->reduce(function($carry, $parte) 
            { 
                // Adds the monto field multiplied by cantidad
                return $carry += ($parte->pivot->monto * $parte->pivot->cantidad); 
            }, 
            // Initial value
            0
        );

        return $amount;
    }

    public function getDiasAttribute()
    {
        $interval = (new DateTime($this->created_at))->diff(new DateTime('tomorrow')); // It counts the whole day
        return $interval->format('%a');
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
    
    public function partes()
    {
        return $this->belongsToMany(Parte::class, 'oc_parte', 'oc_id', 'parte_id')->withPivot(['id', 'descripcion', 'estadoocparte_id', 'cantidad', 'tiempoentrega', 'backorder'])->using(OcParte::class)->withTimestamps();
    }

    public function estadooc()
    {
        return $this->belongsTo(Estadooc::class);
    }

    public function motivobaja()
    {
        return $this->belongsTo(Motivobaja::class);
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

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function despachos()
    {
        return $this->belongsToMany(Despacho::class, 'despacho_oc', 'oc_id', 'despacho_id')->withTimestamps();
    }
}
