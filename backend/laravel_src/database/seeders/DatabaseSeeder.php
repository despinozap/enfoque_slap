<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Marca;
use App\Models\Parte;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //Base object for development testing

        $role = new Role();
        $role->name = 'Administrador';
        $role->save();

        $user = new User();
        $user->name = 'DevAdmin SLAP';
        $user->email = 'admin@mail.com';
        $user->phone = '9012345678';
        $user->password = bcrypt('admin');
        $user->role_id = $role->id;
        $user->save();

        $cliente = new Cliente();
        $cliente->name = 'ClienteTest';
        $cliente->save();

        $marca = new Marca();
        $marca->name = 'MarcaTest';
        $marca->save();

        $parte = new Parte();
        $parte->marca_id = 1;
        $parte->nparte = 'NParteTest';
        $parte->save();
    }
}
