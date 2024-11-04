<?php

namespace Database\Seeders;

use App\Models\Scategoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        //php artisan db:seed --class=ScategoriaSeeder

        $scategorias = [
            ['titulo' => 'Serviço de Bar', 'descricao' => 'Categoria para fornecimento de bebidas e coquetéis para eventos.'],
            ['titulo' => 'Garçom', 'descricao' => 'Categoria para contratação de garçons para atendimento em festas e eventos.'],
            ['titulo' => 'Manobrista', 'descricao' => 'Categoria para serviços de manobrista, oferecendo comodidade aos convidados.'],
            ['titulo' => 'Buffet', 'descricao' => 'Categoria para serviços de buffet, incluindo opções de pratos e sobremesas.'],
            ['titulo' => 'Decoração de Eventos', 'descricao' => 'Categoria para serviços de decoração personalizada para diferentes tipos de eventos.'],
            ['titulo' => 'Música ao Vivo', 'descricao' => 'Categoria para contratação de músicos e bandas para animação de festas.'],
            ['titulo' => 'Fotografia e Filmagem', 'descricao' => 'Categoria para serviços de registro fotográfico e filmagem de eventos.'],
            ['titulo' => 'Iluminação Especial', 'descricao' => 'Categoria para serviços de iluminação decorativa e técnica para eventos.'],
            ['titulo' => 'Locação de Mobiliário', 'descricao' => 'Categoria para locação de mesas, cadeiras e outros móveis para eventos.'],
            ['titulo' => 'Serviços de Limpeza', 'descricao' => 'Categoria para serviços de limpeza antes, durante e após os eventos.']
        ];

        foreach ($scategorias as $scategoria) {
            Scategoria::create($scategoria);
        }
    }
}
