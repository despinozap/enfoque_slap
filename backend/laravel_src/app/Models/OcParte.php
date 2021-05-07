<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OcParte extends Pivot
{
    use HasFactory;

    protected $table = 'oc_parte';
    public $appends = [
        'cantidad_pendiente',
        'cantidad_compradorrecepcionado',
        'cantidad_compradordespachado',
        'cantidad_centrodistribucionrecepcionado',
        'cantidad_centrodistribuciondespachado',
        'cantidad_sucursalrecepcionado',
        'cantidad_sucursaldespachado',
    ];

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

    public function getCantidadPendienteAttribute()
    {
        return $this->cantidad - $this->cantidad_compradorrecepcionado;
    }

    public function getCantidadCompradorRecepcionadoAttribute()
    {
        $ocParteRecepcionList = OcParteRecepcion::join('recepciones', 'recepciones.id', '=', 'ocparte_recepcion.recepcion_id')
                                ->where('recepciones.recepcionable_type', '=', 'App\\Models\\Comprador')->where('ocparte_recepcion.ocparte_id', '=', $this->id)
                                ->get();

        $quantity = $ocParteRecepcionList->reduce(function ($carry, $ocParteRecepcion) 
            {
                return $carry + $ocParteRecepcion->cantidad;
            }, 
            0
        );

        return $quantity;
    }

    public function getCantidadCompradorDespachadoAttribute()
    {
        return 0;
    }

    public function getCantidadCentroDistribucionRecepcionadoAttribute()
    {
        return 0;
    }

    public function getCantidadCentroDistribucionDespachadoAttribute()
    {
        return 0;
    }

    public function getCantidadSucursalRecepcionadoAttribute()
    {
        return 0;
    }

    public function getCantidadSucursalDespachadoAttribute()
    {
        return 0;
    }
}
