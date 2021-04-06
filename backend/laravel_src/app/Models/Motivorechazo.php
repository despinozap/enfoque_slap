<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motivorechazo extends Model
{
    use HasFactory;

    protected $table = 'motivosrechazo';
    protected $fillable = [
        'name', 
    ];
}
