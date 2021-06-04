<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OcParte extends Pivot
{
    use HasFactory;

    protected $table = 'oc_parte';
    protected $fillable = ['id'];

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
