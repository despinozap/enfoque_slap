<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes';

    protected $fillable = [
        'cliente_id', 'user_id', 'estadosolicitud_id', 'comentario', 
    ];

    public function partes()
    {
        return $this->belongsToMany(Parte::class, 'parte_solicitud');
    }
}
