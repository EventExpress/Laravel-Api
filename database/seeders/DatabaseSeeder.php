<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Nome;
use App\Models\Anuncio;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::beginTransaction();
        //caso seja feito em ordem errada a criação dos outros seeders ele nao cria de forma erronea e apresenta o erro

        try {
            Anuncio::factory()->create();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            throw $e;
        }


    }
}
