<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estadooc extends Model
{
    use HasFactory;

    protected $table = 'estadoocs';
    protected $fillable = [
        'name', 
    ];
}
