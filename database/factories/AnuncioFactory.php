<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Anuncio;
use App\Models\ImagemAnuncio;
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
        $usuario = User::factory()->create();

        return [
            'titulo' => $this->faker->sentence,
            'endereco_id' => $endereco->id,
            'capacidade' => $this->faker->numberBetween(1, 200),
            'descricao' => $this->faker->text(100),
            'user_id' => $usuario->id,
            'valor' => $this->faker->randomFloat(2, 10, 1000),
            'agenda' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
        ];
    }

    /**
     * Configurações adicionais após a criação.
     */
    public function configure()
    {
        return $this->afterCreating(function (Anuncio $anuncio) {
            // Seleciona categorias aleatórias para associar ao anúncio
            $categorias = Categoria::inRandomOrder()->take(rand(1, 3))->pluck('id');
            $anuncio->categorias()->attach($categorias);

            ImagemAnuncio::create([
                'anuncio_id' => $anuncio->id,
                'image_path' => $this->faker->imageUrl(640, 480, 'business'),
                'is_main' => true,
            ]);
        });
    }
}
