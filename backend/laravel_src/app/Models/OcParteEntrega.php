<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OcParteEntrega extends Pivot
{
    use HasFactory;

    protected $table = 'entrega_ocparte';

    public function ocparte()
    {
        return $this->belongsTo(OcParte::class);
    }
}
