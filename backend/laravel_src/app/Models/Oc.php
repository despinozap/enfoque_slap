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
