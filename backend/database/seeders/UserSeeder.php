<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $name  = env('ADMIN_NAME', 'Heber Almeida');
        $email = env('ADMIN_EMAIL', 'heber@email.com');
        $cpf   = env('ADMIN_CPF', '00000000191');
        $pass  = env('ADMIN_PASSWORD', '123456');

        // garante role admin
        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'uuid'              => (string) Str::uuid(), // 🔹 seta manual
                'name'              => $name,
                'cpf'               => $cpf,
                'password'          => Hash::make($pass),
                'email_verified_at' => now(),
                'sector_id'         => 1, // SEDE
            ]
        );

        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
