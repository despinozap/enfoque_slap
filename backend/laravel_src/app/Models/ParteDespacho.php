<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ParteDespacho extends Pivot
{
    use HasFactory;

    protected $table = 'despacho_parte';

    public function parte()
    {
        return $this->belongsTo(Parte::class);
    }

    public function despacho()
    {
        return $this->belongsTo(Despacho::class);
    }
}
