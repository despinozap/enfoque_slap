<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comprador extends Model
{
    use HasFactory;

    protected $table = 'compradores';
    protected $fillable = [
        'rut', 'name', 'address', 'city', 'contact', 'phone', 'country_id', 
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    
    public function proveedores()
    {
        return $this->hasMany(Proveedor::class);
    }

    public function recepciones()
    {
        return $this->morphMany(Recepcion::class, 'recepcionable');
    }

    public function despachos()
    {
        return $this->morphMany(Despacho::class, 'despachable');
    }
}
