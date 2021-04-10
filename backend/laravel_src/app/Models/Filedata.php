<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filedata extends Model
{
    use HasFactory;

    protected $table = 'filedatas';
    protected $fillable = [
        'path', 'size',
    ];
}
