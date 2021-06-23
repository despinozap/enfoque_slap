<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';
    protected $fillable = ['rut', 'name', 'address', 'city'];

    public function users()
    {
        return $this->morphMany(User::class, 'stationable');
    }

    public function recepciones()
    {
        return $this->morphMany(Recepcion::class, 'recepcionable');
    }

    public function despachos()
    {
        return $this->morphMany(Despacho::class, 'despachable');
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class);
    }
    

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
