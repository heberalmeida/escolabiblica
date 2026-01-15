<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = array_merge(['SEDE'], array_map(fn($i)=>"Setor $i", range(1,20)));
        foreach ($names as $name) \App\Models\Sector::firstOrCreate(['name'=>$name]);
    }
}
