<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OcParte extends Pivot
{
    use HasFactory;

    protected $table = 'oc_parte';

    public function oc()
    {
        return $this->belongsTo(Oc::class);
    }

    public function parte()
    {
        return $this->belongsTo(Parte::class);
    }

    public function estadoocparte() 
    {
        return $this->belongsTo(Estadoocparte::class);
    }

    public function getCantidadPendiente()
    {
        return $this->cantidad - $this->getCantidadRecepcionado($this->oc->cotizacion->solicitud->comprador);
    }

    public function getCantidadRecepcionado($recepcionable)
    {
        $ocParteRecepcionList = OcParteRecepcion::join('recepciones', 'recepciones.id', '=', 'ocparte_recepcion.recepcion_id')
                                ->where('recepciones.recepcionable_type', '=', get_class($recepcionable))
                                ->where('recepciones.recepcionable_id', '=', $recepcionable->id)
                                ->where('ocparte_recepcion.ocparte_id', '=', $this->id)
                                ->get();

        $quantity = $ocParteRecepcionList->reduce(function($carry, $ocParteRecepcion) 
            {
                return $carry + $ocParteRecepcion->cantidad;
            }, 
            0
        );

        return $quantity;
    }

    public function getCantidadDespachado($despachable)
    {
        $ocParteDespachoList = OcParteDespacho::join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                ->where('despachos.despachable_type', '=', get_class($despachable))
                                ->where('despachos.despachable_id', '=', $despachable->id)
                                ->where('despacho_ocparte.ocparte_id', '=', $this->id)
                                ->get();

        $quantity = $ocParteDespachoList->reduce(function ($carry, $ocParteDespacho) 
            {
                return $carry + $ocParteDespacho->cantidad;
            }, 
            0
        );

        return $quantity;
    }
}
