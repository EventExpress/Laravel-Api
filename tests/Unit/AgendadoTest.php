<?php

use App\Models\Endereco;
use App\Models\Agendado;
use App\Models\Anuncio;
use App\Models\Servico;
use App\Models\TypeUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('incluir dados incompletos no formulario de reserva', function () {
    $user = User::factory()->create();
    TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locatario']);
    $this->actingAs($user);

    $response = $this->postJson('/api/agendados', [
        'anuncio_id' => null,
        'servico_id' => [],
        'formapagamento' => '',
        'data_inicio' => '2024-11-10',
        'data_fim' => '2024-12-10',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['anuncio_id', 'formapagamento']);
});

test('criar uma reserva corretamente', function () {
    $user = User::factory()->create();
    //TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locatario']);
    $this->actingAs($user);

    $locador = User::factory()->create();
    //TypeUser::create(['user_id' => $locador->id, 'tipousu' => 'locador']);

    $this->seed(CategoriaSeeder::class);

    $endereco = Endereco::create([
        'cidade' => 'Curitiba',
        'cep' => '81925-187',
        'numero' => '199',
        'bairro' => 'Sitio Cercado',
    ]);

    $anuncio = Anuncio::create([
        'user_id' => $locador->id,
        'titulo' => 'Festa de Casamento',
        'endereco_id' => $endereco->id,
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
    ]);

    $servico = Servico::factory()->create();

    $response = $this->postJson('/api/agendados', [
        'servico_id' => [$servico->id],
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'dinheiro',
        'data_inicio' => '2024-11-10',
        'data_fim' => '2024-12-10',
    ]);


    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Reserva criada com sucesso.',
             ]);
});

test('atualizar reserva com sucesso', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $locador = User::factory()->create();

    $this->seed(CategoriaSeeder::class);

    $endereco = Endereco::create([
        'cidade' => 'Curitiba',
        'cep' => '81925-187',
        'numero' => '199',
        'bairro' => 'Sitio Cercado',
    ]);

    $anuncio = Anuncio::create([
        'user_id' => $locador->id,
        'titulo' => 'Festa de Casamento',
        'endereco_id' => $endereco->id,
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
    ]);

    $servico = Servico::factory()->create();

    $agendado = Agendado::create([
        'user_id' => $user->id,
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'dinheiro',
        'data_inicio' => '2024-11-10',
        'data_fim' => '2024-12-10',
    ]);

    $response = $this->putJson("/api/agendados/{$agendado->id}", [
        'anuncio_id' => $anuncio->id,
        'data_inicio' => '2024-11-20',
        'data_fim' => '2024-12-20',
        'servicoId' => [$servico->id],
        'formapagamento' => 'cartao',
    ]);

    $response->assertStatus(200)
             ->assertJson([
                 'status' => true,
                 'message' => 'Reserva atualizado com sucesso.',
             ]);
});

test('cancelar reserva com sucesso', function () {

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $locador = User::factory()->create();
    $this->seed(CategoriaSeeder::class);

    $endereco = Endereco::create([
        'cidade' => 'Curitiba',
        'cep' => '81925-187',
        'numero' => '199',
        'bairro' => 'Sitio Cercado',
    ]);

    $anuncio = Anuncio::create([
        'user_id' => $locador->id,
        'titulo' => 'Festa de Casamento',
        'endereco_id' => $endereco->id,
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => '2024-12-12',
    ]);

    $servico = Servico::factory()->create();
    $agendado = Agendado::create([
        'user_id' => $user->id,
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'dinheiro',
        'data_inicio' => '2024-11-10',
        'data_fim' => '2024-12-10',
    ]);

    $response = $this->deleteJson("/api/agendados/{$agendado->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'status' => true,
                 'message' => 'Reserva cancelada com sucesso.',
             ]);
});

test('cancelar reserva inexistente', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Tenta cancelar uma reserva com um ID que não existe
    $nonExistentId = 9999;

    $response = $this->deleteJson("/api/agendados/{$nonExistentId}");

    $response->assertStatus(403)
             ->assertJson([
                 'status' => false,
                 'error' => 'Reserva não encontrada ou você não tem permissão para excluí-la.'
             ]);
});