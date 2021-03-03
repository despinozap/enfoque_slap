<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estadosolicitud extends Model
{
    use HasFactory;

    protected $table = 'estadosolicitudes';

    protected $fillable = [
        'name',
    ];
}
