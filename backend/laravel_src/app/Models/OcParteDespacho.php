<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OcParteDespacho extends Pivot
{
    use HasFactory;

    protected $table = 'despacho_ocparte';
}
