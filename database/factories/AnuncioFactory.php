<?php

namespace Database\Factories;

use App\Models\Avaliacao;
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

            $usuarioId = User::inRandomOrder()->first()->id; // Obtém um usuário aleatório

            // Cria uma avaliação associada a esse usuário
            $avaliacao = Avaliacao::factory()->make(); // Cria uma avaliação
            $avaliacao->avaliavel_type = "Anuncio"; // Define o tipo polimórfico
            $avaliacao->avaliavel_id = $anuncio->id; // Associa à ID do anúncio
            $avaliacao->user_id = $usuarioId; // Associa o ID do usuário que fez a avaliação
            $avaliacao->save(); // Salva a avaliação
        });
    }
}
