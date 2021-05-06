<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recepcion extends Model
{
    use HasFactory;

    protected $table = 'recepciones';
    public $appends = ['partes_total'];

    public function recepcionable()
    {
        return $this->morphTo();
    }

    public function ocpartes()
    {
        return $this->belongsToMany(OcParte::class, 'ocparte_recepcion', 'recepcion_id', 'oc_parte_id')->withPivot(['cantidad', 'comentario'])->withTimestamps();
    }

    public function getPartesTotalAttribute()
    {
        $quantity = 0;

        foreach($this->ocpartes as $ocparte)
        {
            $quantity += $ocparte->pivot->cantidad;
        }

        $this->attributes['partes_total'] = $quantity;
        
        return $quantity;
    }

    public function proveedorrecepcion()
    {
        return $this->hasOne(Proveedorrecepcion::class);
    }
}
