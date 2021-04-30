<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motivobaja extends Model
{
    //This model represents a drop reason for OCs
    use HasFactory;

    protected $table = 'motivosbaja';
    protected $fillable = [
        'name', 
    ];
}
