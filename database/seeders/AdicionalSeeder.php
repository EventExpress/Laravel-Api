<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Adicional;

class AdicionalSeeder extends Seeder
{
    public function run()
    {

        //para executar essa seeder é necessario rodar este comando php artisan db:seed --class=AdicionalSeeder
        $adicionais = [
            [
                'titulo' => 'Bebidas',
                'valor' => 10.00,
                'descricao' => 'Variedade de bebidas alcoólicas e não alcoólicas.'
            ],
            [
                'titulo' => 'Decoração Temática',
                'valor' => 400.00,
                'descricao' => 'Decoração personalizada conforme o tema da festa.'
            ],
            [
                'titulo' => 'Buffet Completo',
                'valor' => 40.00,
                'descricao' => 'Inclui buffet completo com entrada, prato principal e sobremesa.'
            ],
            [
                'titulo' => 'Música ao Vivo',
                'valor' => 600.00,
                'descricao' => 'Show ao vivo com banda local.'
            ],
            [
                'titulo' => 'Fotógrafo Profissional',
                'valor' => 1500.00,
                'descricao' => 'Serviço de fotografia profissional durante o evento.'
            ],
            [
                'titulo' => 'Aluguel de Tendas',
                'valor' => 150.00,
                'descricao' => 'Tendas para proteção contra o sol ou chuva.'
            ],
            [
                'titulo' => 'Serviço de Valet',
                'valor' => 300.00,
                'descricao' => 'Serviço de estacionamento com manobrista.'
            ],
            [
                'titulo' => 'Lembrancinhas Personalizadas',
                'valor' => 10.00,
                'descricao' => 'Lembranças personalizadas para os convidados.'
            ],
            [
                'titulo' => 'Cantinho Kids',
                'valor' => 150.00,
                'descricao' => 'Espaço dedicado para as crianças com monitoria.'
            ],
            [
                'titulo' => 'Open Bar',
                'valor' => 30.00,
                'descricao' => 'Bar completo com drinks e coquetéis.'
            ],
        ];

        foreach ($adicionais as $adicional) {
            Adicional::create($adicional);
        }
    }
}
