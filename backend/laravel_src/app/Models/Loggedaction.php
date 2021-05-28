<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loggedaction extends Model
{
    use HasFactory;

    protected $table = 'loggedactions';
    protected $fillable = ['user_id', 'loggeable_id', 'loggeable_type', 'description'];
}
