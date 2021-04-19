<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use DateTime;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones';
    protected $fillable = [
        'solicitud_id', 'estadocotizacion_id', 'motivorechazo_id', 'usdvalue',
    ];
    public $appends = ['partes_total', 'dias'];

    public function getUsdMontoAttribute()
    {
        $amount = 0;

        foreach($this->partes as $parte)
        {
            $amount += $parte->pivot->monto;
        }

        return $amount;
    }

    public function getDiasAttribute()
    {
        $interval = (new DateTime($this->updated_at))->diff(new DateTime('tomorrow')); // It counts the whole day
        return $interval->format('%a');
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

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function estadocotizacion()
    {
        return $this->belongsTo(Estadocotizacion::class);
    }

    public function motivorechazo()
    {
        return $this->belongsTo(Motivorechazo::class);
    }

    public function partes()
    {
        return $this->belongsToMany(Parte::class, 'cotizacion_parte')->withPivot('cantidad', 'descripcion', 'costo', 'margen', 'tiempoentrega', 'peso', 'flete', 'monto', 'backorder');
    }
}
