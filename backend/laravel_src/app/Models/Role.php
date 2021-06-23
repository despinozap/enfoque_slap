<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /*
     *  Roles:
     *      suadm: Super Administrator
     *      admin: Administrador
     *      vensol: Vendedor solicitante (Vendedor en Sucursal)
     *      agtcom: Agente de compra en Comprador
     *      colcom: Coordinador Logistico comprador (bodega en Comprador)
     *      colsol: Coordinador Logistico solicitante (Bodega en Sucursal)
    */


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
