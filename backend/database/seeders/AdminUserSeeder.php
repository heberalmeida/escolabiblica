<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Garante que role admin existe
        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::firstOrCreate(
            ['cpf' => '00000000191'], // CPF fictício válido
            [
                'name'      => 'Administrador',
                'email'     => 'admin@site.com',
                'phone'     => '11999999999',
                'password'  => bcrypt('123456'),
                'sector_id' => 1, // SEDE
            ]
        );

        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
