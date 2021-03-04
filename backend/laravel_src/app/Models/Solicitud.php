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

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function estadosolicitud()
    {
        return $this->belongsTo(Estadosolicitud::class);
    }

    public function partes()
    {
        return $this->belongsToMany(Parte::class, 'parte_solicitud')->withPivot('cantidad');
    }
}
