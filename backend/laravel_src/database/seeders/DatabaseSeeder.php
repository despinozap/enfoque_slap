<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // User::factory(10)->create();
        $rol = new Rol();
        $rol->nombre = 'Administrador';
        $rol->save();

        $user = new User();
        $user->nombre = 'DevAdmin SLAP';
        $user->email = 'admin@email.com';
        $user->telefono = '+123456789';
        $user->password = bcrypt('admin');
        $user->rol_id = $rol->id;
        $user->save();
    }
}
