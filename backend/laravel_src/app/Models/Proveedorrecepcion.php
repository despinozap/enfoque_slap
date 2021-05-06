<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Proveedorrecepcion extends Pivot
{
    use HasFactory;

    protected $table = 'proveedor_recepcion';

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function recepcion()
    {
        return $this->belongsTo(Recepcion::class, 'recepcion_id');
    }
}
