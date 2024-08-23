<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        //para executar esta seeder é necessario rodar
        //php artisan db:seed --class=CategoriaSeeder

        $categorias = [
            ['titulo' => 'Casamento', 'descricao' => 'Categoria para serviços relacionados a casamentos e cerimônias.'],
            ['titulo' => 'Festa Corporativa', 'descricao' => 'Categoria para eventos corporativos e empresariais.'],
            ['titulo' => 'Festa Infantil', 'descricao' => 'Categoria para festas e eventos voltados para crianças.'],
            ['titulo' => 'Aniversário', 'descricao' => 'Categoria para celebrações de aniversários.'],
            ['titulo' => 'Formatura', 'descricao' => 'Categoria para eventos de formatura e colação de grau.'],
            ['titulo' => 'Conferência', 'descricao' => 'Categoria para conferências e eventos acadêmicos.'],
            ['titulo' => 'Workshop', 'descricao' => 'Categoria para workshops e cursos práticos.'],
            ['titulo' => 'Seminário', 'descricao' => 'Categoria para seminários e palestras técnicas.'],
            ['titulo' => 'Feira', 'descricao' => 'Categoria para feiras e exposições comerciais.'],
            ['titulo' => 'Show', 'descricao' => 'Categoria para eventos de entretenimento e shows.'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}
