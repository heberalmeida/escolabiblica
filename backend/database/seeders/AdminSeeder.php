<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@site.com'],
            [
                'name' => 'Admin',
                'cpf' => '00000000000',
                'phone' => '000000000',
                'sector_id' => null,
                'password' => bcrypt('123456'),
            ]
        );
        $admin->assignRole($adminRole);

        $supervisor = User::firstOrCreate(
            ['email' => 'supervisor@site.com'],
            [
                'name' => 'Supervisor',
                'cpf' => '11111111111',
                'phone' => '000000000',
                'sector_id' => null,
                'password' => bcrypt('123456'),
            ]
        );
        $supervisor->assignRole($supervisorRole);
    }
}
