<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function routepermissions()
    {
        return $this->belongsToMany(Routepermission::class, 'role_routepermission');
    }

    public function hasRoutepermission($routeName)
    {
        if($this->belongsToMany(Routepermission::class, 'role_routepermission')->where('name', $routeName)->first())
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
