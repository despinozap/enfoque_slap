<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function faenas()
    {
        return $this->hasMany(Faena::class);
    }
    
    public function getSolicitudesAttribute()
    {
        $ids = $this->faenas->map(function($item, $key)
        {
            return $item->id;
        });

        return Solicitud::whereIn('faena_id', $ids)->get(); 
    }
}
