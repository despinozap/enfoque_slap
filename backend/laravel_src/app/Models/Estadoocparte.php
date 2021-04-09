<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estadoocparte extends Model
{
    use HasFactory;

    protected $table = 'estadoocpartes';
    protected $fillable = [
        'name', 
    ];
}
