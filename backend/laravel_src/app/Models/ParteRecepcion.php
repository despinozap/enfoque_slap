<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ParteRecepcion extends Pivot
{
    use HasFactory;

    protected $table = 'parte_recepcion';

    public function parte()
    {
        return $this->belongsTo(Parte::class);
    }
}
