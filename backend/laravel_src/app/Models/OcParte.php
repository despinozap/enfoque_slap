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
        $ocParteRecepcionList = OcParteRecepcion::join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
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

    public function getCantidadEntregado()
    {
        $ocParteEntregaList = OcParteEntrega::where('entrega_ocparte.ocparte_id', '=', $this->id)->get();

        $quantity = $ocParteEntregaList->reduce(function($carry, $ocParteEntrega) 
            {
                return $carry + $ocParteEntrega->cantidad;
            }, 
            0
        );

        return $quantity;
    }
}
