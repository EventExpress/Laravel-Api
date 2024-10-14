<?php

namespace Database\Factories;

use App\Models\Avaliacao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Avaliacao>
 */
class AvaliacaoFactory extends Factory
{
    protected $model = Avaliacao::class;

    public function definition()
    {
        return [
            'avaliavel_type' => 'Anuncio', // ESTATICO PARA ANUNCIO PARA CRITERIOS DE TESTE
            'avaliavel_id' => null, // este campo será preenchido ao usar a factory
            'user_id' => User::factory(), // Cria um usuário para avaliação
            'nota' => $this->faker->numberBetween(1, 5),
            'comentario' => $this->faker->sentence,
        ];
    }
}

