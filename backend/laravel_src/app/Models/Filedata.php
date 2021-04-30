<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Storage;

class Filedata extends Model
{
    use HasFactory;

    protected $table = 'filedatas';
    protected $fillable = [
        'path', 'size',
    ];
    public $appends = ['url'];

    public function getUrlAttribute()
    {
        return asset(Storage::url($this->path));
    }
}
