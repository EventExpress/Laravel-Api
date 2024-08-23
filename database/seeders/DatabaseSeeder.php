<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Nome;
use App\Models\Anuncio;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
    //User::factory()->create();
    User::factory()->create();
    }
}
