<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones';
    protected $fillable = [
        'solicitud_id', 'estadocotizacion_id', 'usdvalue',
    ];
    public $appends = ['partes_total'];

    public function getPartesTotalAttribute()
    {
        $quantity = 0;

        foreach($this->partes as $parte)
        {
            $quantity += $parte->pivot->cantidad;
        }

        return $quantity;
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function estadocotizacion()
    {
        return $this->belongsTo(Estadocotizacion::class);
    }

    public function partes()
    {
        return $this->belongsToMany(Parte::class, 'cotizacion_parte')->withPivot('cantidad', 'descripcion', 'costo', 'margen', 'tiempoentrega', 'peso', 'flete', 'monto', 'backorder');
    }
}
