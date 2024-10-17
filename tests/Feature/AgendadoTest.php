<?php


use App\Http\Controllers\AgendadoController;
use App\Models\Servico;
use App\Models\User;
use App\Models\Agendado;
use App\Models\Anuncio;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('impede reserva para datas já ocupadas', function () {
    // Cria um usuário e autentica
    $user = User::factory()->create();

    // Autentica o usuário
    $this->actingAs($user);


    $anuncio = Anuncio::factory()->create();

    Agendado::create([
        'user_id' => $user->id,
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'cartao',
        'data_inicio' => '2024-10-16',
        'data_fim' => '2024-10-18',
    ]);

    // Faz a requisição POST para tentar criar uma nova reserva nas mesmas datas
    $response = $this->postJson('/api/agendados', [
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'cartao',
        'data_inicio' => '2024-10-16',
        'data_fim' => '2024-10-18',
    ]);

    $response->assertStatus(409);
});

it('conclui a reserva sem internet e retorna mensagem de erro', function () {
    // Cria um usuário e autentica
    $user = User::factory()->create();
    $this->actingAs($user);

    // Cria um anúncio
    $anuncio = Anuncio::factory()->create();

    // Cria uma reserva existente
    $agendado = Agendado::create([
        'user_id' => $user->id,
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'cartao',
        'data_inicio' => '2024-10-16',
        'data_fim' => '2024-10-18',
    ]);

    // Simula a ausencia de internet através de Mockery
    $this->mock(AgendadoController::class, function ($mock) {
        $mock->shouldReceive('store')
            ->andThrow(new \Exception('Erro 404 - Rede indisponível, tente novamente mais tarde'));
    });

    // Faz a requisição POST para tentar criar uma nova reserva
    $response = $this->postJson('/api/agendados', [
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'cartao',
        'data_inicio' => '2024-10-16',
        'data_fim' => '2024-10-18',
    ]);

    // Verifica se o status é 500 (erro interno do servidor)
    $response->assertStatus(500);
    $response->assertJson([
        'message' => 'Erro 404 - Rede indisponível, tente novamente mais tarde',
        'exception' => 'Exception',
    ]);
});

