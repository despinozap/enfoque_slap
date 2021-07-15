<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';
    protected $fillable = [
        'comprador_id', 
        'rut', 
        'name', 
        'address', 
        'city', 
        'email', 
        'phone',
        'delivered',
        'delivery_name',
        'delivery_address',
        'delivery_city',
        'delivery_email',
        'delivery_phone'
    ];

    public function comprador()
    {
        return $this->belongsTo(Comprador::class);
    }

    public function ocs()
    {
        return $this->hasMany(Oc::class);
    }

    public function recepciones()
    {
        // Retrieves all the Recepcion where the Proveedor was a source
        return $this->morphMany(Recepcion::class, 'sourceable');
    }
}
