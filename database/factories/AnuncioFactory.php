<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Anuncio;
use App\Models\User;
use App\Models\Endereco;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Anuncio>
 */
class AnuncioFactory extends Factory

{

    protected $model = Anuncio::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $endereco = Endereco::factory()->create();
        $user = User::factory()->create();
        //$categoria = Categoria::factory()->create();

        return [
            'titulo'=> $this->faker->sentence,
            'endereco_id'=>$endereco->id,
            'capacidade'=> $this->faker->numberBetween(1, 200),
            'descricao' => $this->faker->text(30),
            'user_id'=>$user->id,
            'valor'=> $this->faker->randomFloat(2, 10, 1000),
            'agenda' => $this->faker->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
        ];
    }
}
