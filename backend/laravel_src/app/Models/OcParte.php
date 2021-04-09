<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OcParte extends Pivot
{
    use HasFactory;

    protected $table = 'oc_parte';

    public function estadoocparte() 
    {
        return $this->belongsTo(Estadoocparte::class);
    }
}
