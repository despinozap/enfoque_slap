<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OcParte extends Pivot
{
    use HasFactory;

    protected $table = 'oc_parte';
    protected $fillable = ['id', 'descripcion', 'cantidad', 'tiempoentrega', 'backorder', 'estadoocparte_id'];

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

    public function getCantidadRecepcionado($recepcionable)
    {
        $ocParteRecepcionList = OcParteRecepcion::select('recepcion_ocparte.*')
                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                            ->where('recepciones.recepcionable_type', '=', get_class($recepcionable))
                            ->where('recepciones.recepcionable_id', '=', $recepcionable->id)
                            ->where('recepcion_ocparte.ocparte_id', '=', $this->id)
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
        $ocParteDespachoList = OcParteDespacho::select('despacho_ocparte.*')
                            ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                            ->where('despachos.despachable_type', '=', get_class($despachable))
                            ->where('despachos.despachable_id', '=', $despachable->id)
                            ->where('despacho_ocparte.ocparte_id', '=', $this->id)
                            ->get();

        $quantity = $ocParteDespachoList->reduce(function($carry, $ocParteDespacho) 
            {
                return $carry + $ocParteDespacho->cantidad;
            }, 
            0
        );

        return $quantity;
    }

    public function getCantidadEntregado($sucursal)
    {
        $ocParteEntregaList = OcParteEntrega::select('entrega_ocparte.*')
                            ->join('entregas', 'entregas.id', '=', 'entrega_ocparte.entrega_id')
                            ->join('oc_parte', 'oc_parte.id', '=', 'entrega_ocparte.ocparte_id')
                            ->where('oc_parte.id', '=', $this->id)
                            ->where('entregas.sucursal_id', '=', $sucursal->id)
                            ->get();

        $quantity = $ocParteEntregaList->reduce(function ($carry, $ocParteEntrega) 
            {
                return $carry + $ocParteEntrega->cantidad;
            }, 
            0
        );

        return $quantity;
    }

    public function getCantidadTotalEntregado()
    {
        $ocParteEntregaList = OcParteEntrega::select('entrega_ocparte.*')
                            ->join('entregas', 'entregas.id', '=', 'entrega_ocparte.entrega_id')
                            ->join('oc_parte', 'oc_parte.id', '=', 'entrega_ocparte.ocparte_id')
                            ->where('oc_parte.id', '=', $this->id)
                            ->get();

        $quantity = $ocParteEntregaList->reduce(function ($carry, $ocParteEntrega) 
            {
                return $carry + $ocParteEntrega->cantidad;
            }, 
            0
        );

        return $quantity;
    }

    public function loggedactions()
    {
        return $this->morphMany(Loggedaction::class, 'loggeable');
    }

}
