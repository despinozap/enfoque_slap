<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
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
    }
}
