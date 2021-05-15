<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OcParteRecepcion extends Pivot
{
    use HasFactory;

    protected $table = 'ocparte_recepcion';

    public function ocparte()
    {
        return $this->belongsTo(OcParte::class);
    }
}
