<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SectorSeeder::class,   // cria SEDE e Setores 1..20
            UserSeeder::class,     // cria Admin vinculado à SEDE
            EventSeeder::class,    // limpa produtos e cria evento da Escola Bíblica
        ]);
    }
}
