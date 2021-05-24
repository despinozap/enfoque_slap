<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parte extends Model
{
    use HasFactory;

    protected $fillable = [
        'marca_id', 'nparte' 
    ];

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function solicitudes()
    {
        return $this->belongsToMany(Solicitud::class, 'parte_solicitud');
    }

    public function getCantidadRecepcionado($recepcionable)
    {
        $parteRecepcionList = ParteRecepcion::join('recepciones', 'recepciones.id', '=', 'parte_recepcion.recepcion_id')
                            ->where('recepciones.recepcionable_type', '=', get_class($recepcionable))
                            ->where('recepciones.recepcionable_id', '=', $recepcionable->id)
                            ->where('parte_recepcion.parte_id', '=', $this->id)
                            ->get();

        $quantity = $parteRecepcionList->reduce(function($carry, $parteRecepcion) 
            {
                return $carry + $parteRecepcion->cantidad;
            }, 
            0
        );

        return $quantity;
    }
    
    public function getCantidadRecepcionado_sourceable($recepcionable, $sourceable)
    {
        $parteRecepcionList = ParteRecepcion::join('recepciones', 'recepciones.id', '=', 'parte_recepcion.recepcion_id')
                            ->where('recepciones.recepcionable_type', '=', get_class($recepcionable))
                            ->where('recepciones.recepcionable_id', '=', $recepcionable->id)
                            ->where('recepciones.sourceable_type', '=', get_class($sourceable))
                            ->where('recepciones.sourceable_id', '=', $sourceable->id)
                            ->where('parte_recepcion.parte_id', '=', $this->id)
                            ->get();

        $quantity = $parteRecepcionList->reduce(function($carry, $parteRecepcion) 
            {
                return $carry + $parteRecepcion->cantidad;
            }, 
            0
        );

        return $quantity;
    }

    public function getCantidadDespachado($despachable)
    {
        $parteDespachoList = ParteDespacho::join('despachos', 'despachos.id', '=', 'despacho_parte.despacho_id')
                            ->where('despachos.despachable_type', '=', get_class($despachable))
                            ->where('despachos.despachable_id', '=', $despachable->id)
                            ->where('despacho_parte.parte_id', '=', $this->id)
                            ->get();

        $quantity = $parteDespachoList->reduce(function ($carry, $parteDespacho) 
            {
                return $carry + $parteDespacho->cantidad;
            }, 
            0
        );

        return $quantity;
    }

    public function getCantidadDespachado_destinable($despachable, $destinable)
    {
        $parteDespachoList = ParteDespacho::join('despachos', 'despachos.id', '=', 'despacho_parte.despacho_id')
                            ->where('despachos.despachable_type', '=', get_class($despachable))
                            ->where('despachos.despachable_id', '=', $despachable->id)
                            ->where('despachos.destinable_type', '=', get_class($destinable))
                            ->where('despachos.destinable_id', '=', $destinable->id)
                            ->where('despacho_parte.parte_id', '=', $this->id)
                            ->get();

        $quantity = $parteDespachoList->reduce(function ($carry, $parteDespacho) 
            {
                return $carry + $parteDespacho->cantidad;
            }, 
            0
        );

        return $quantity;
    }
}
