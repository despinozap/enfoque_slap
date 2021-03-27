<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faena extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 'name' 
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}