<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estadocotizacion extends Model
{
    use HasFactory;

    protected $table = 'estadocotizaciones';

    protected $fillable = [
        'name',
    ];
}
