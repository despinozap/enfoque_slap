<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Routepermission;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Marca;
use App\Models\Parte;
use App\Models\Estadosolicitud;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        /*
        *   Route permissions
        */
        $routePermissionNames = [
            //Roles
            'roles indexfull',
            //Users
            'users index',
            'users store',
            'users show',
            'users update',
            'users destroy',
            //Clientes
            'clientes indexfull',
            //Marcas
            'marcas indexfull',
            //Solicitudes
            'solicitudes index',
            'solicitudes store',
            'solicitudes show',
            'solicitudes update'
        ];

        //Add route permissions
        foreach($routePermissionNames as $routePermissionName)
        {
            $routepermission = new Routepermission();
            $routepermission->name = $routePermissionName;
            $routepermission->save();
        }

        /*
        *   Roles
        */

        //Administrador
        {
            $role = new Role();
            $role->name = 'Administrador';
            $role->save();

            //Route permissions to for Role: Administrador
            $routePermissionNames = [
                //Roles
                'roles indexfull',
                //Users
                'users index',
                'users store',
                'users show',
                'users update',
                'users destroy',
                //Clientes
                'clientes indexfull',
                //Marcas
                'marcas indexfull',
                //Solicitudes
                'solicitudes index',
                'solicitudes store',
                'solicitudes show',
                'solicitudes update'
            ];

            $routePermissionIds = [];

            //Get all the Routepermissions IDs
            foreach($routePermissionNames as $routePermissionName)
            {
                $routePermission = Routepermission::where('name', $routePermissionName)->first();
                $routePermissionIds[] = $routePermission->id;
            }

            //Sync permissions to the role
            $role->routepermissions()->sync($routePermissionIds);
        }
        

        /*
        *   Users
        */
        $user = new User();
        $user->name = 'DevAdmin SLAP';
        $user->email = 'admin@mail.com';
        $user->phone = '9012345678';
        $user->password = bcrypt('admin');
        $user->role_id = $role->id;
        $user->save();

        /*
        *   Clientes
        */
        $cliente = new Cliente();
        $cliente->name = 'ClienteTest';
        $cliente->save();

        /*
        *   Marcas
        */
        $marca = new Marca();
        $marca->name = 'MarcaTest';
        $marca->save();

        /*
        *   Partes
        */
        $parte = new Parte();
        $parte->marca_id = 1;
        $parte->nparte = 'NParteTest';
        $parte->save();

        /*
        *   Estado solicitudes
        */
        $estadosolicitud = new Estadosolicitud();
        $estadosolicitud->name = 'EstadosolicitudTest';
        $estadosolicitud->save();
    }
}
