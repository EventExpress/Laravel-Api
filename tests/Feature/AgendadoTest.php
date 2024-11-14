<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AgendadoController;
use App\models\Categoria;
use App\Models\TypeUser;
use App\Models\Servico;
use App\Models\User;
use App\Models\Agendado;
use App\Models\Anuncio;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile; 
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('impede reserva para datas já ocupadas', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $anuncio = Anuncio::factory()->create();
    $servico = Servico::factory()->create();

    Agendado::create([
        'user_id' => $user->id,
        'servicoId' => [$servico->id],
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'pix',
        'data_inicio' => '2024-10-16',
        'data_fim' => '2024-10-18',
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2024-10-16',
                'data_fim' => '2024-10-18',
            ]
        ],
    ]);

    $response = $this->postJson("/api/agendados/{$anuncio->id}", [
        'formapagamento' => 'pix',
        'data_inicio' => '2024-10-16',
        'data_fim' => '2024-10-18',
        'user_id' => $user->id,
        'servicoId' => [$servico->id],
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2024-10-16',
                'data_fim' => '2024-10-18',
            ]
        ],
    ]);
    

    $response->assertStatus(500);
});

it('tenta concluir a reserva sem internet e retorna mensagem de erro', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $anuncio = Anuncio::factory()->create();

    $agendado = Agendado::create([
        'user_id' => $user->id,
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'pix',
        'data_inicio' => '2024-10-16',
        'data_fim' => '2024-10-18',
    ]);

    // Simula a ausencia de internet através de Mockery
    $this->mock(AgendadoController::class, function ($mock) {
        $mock->shouldReceive('store')
            ->andThrow(new \Exception('Erro 404 - Rede indisponível, tente novamente mais tarde'));
    });

    $response = $this->postJson("/api/agendados/{$anuncio->id}", [
        'formapagamento' => 'pix',
        'data_inicio' => '2024-10-16',
        'data_fim' => '2024-10-18',
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'message' => 'Erro 404 - Rede indisponível, tente novamente mais tarde',
        'exception' => 'Exception',
    ]);
});

it('Pesquisar reserva inexistente', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $locador = User::factory()->create();
    $this->seed(CategoriaSeeder::class);
    $categorias = Categoria::all();
    $imagens = [
        base64_encode(UploadedFile::fake()->image('imagem1.jpg')->getContent()),
        base64_encode(UploadedFile::fake()->image('imagem2.jpg')->getContent()),
    ];

    $response = $this->postJson('/api/anuncios', [
        'user_id' => $locador->id,
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '81925-187',
        'numero' => '199',
        'bairro' => 'Sitio Cercado',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => ['data' => '2025-09-18'],
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
        'imagens' => $imagens,
    ]);

    $anuncio = Anuncio::latest()->first();
    $servico = Servico::factory()->create();

    $response = $this->postJson("/api/agendados/{$anuncio->id}", [
        'servicoId' => [$servico->id],
        'formapagamento' => 'pix',
        'data_inicio' => '2023-10-10', 
        'data_fim' => '2023-11-10',
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2023-10-10',
                'data_fim' => '2023-11-10',
            ]
        ],    
    ]);

    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Reserva criada com sucesso.',
             ]);

    $response = $this->getJson("/api/agendados/{$anuncio->id}?search=2024-10-10");//ano errado
    $response->assertStatus(404)
             ->assertJson([
                 'status' => false,
                 'message' => 'Reserva não encontrada.',
             ]);
});

it('impede alteração de reserva para datas já ocupadas', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $anuncio = Anuncio::factory()->create();
    $servico = Servico::factory()->create();

    Agendado::create([
        'user_id' => $user->id,
        'servicoId' => [$servico->id],
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'pix',
        'data_inicio' => '2024-10-16',
        'data_fim' => '2024-10-18',
    ]);

    $response = $this->postJson("/api/agendados/{$anuncio->id}", [
        'formapagamento' => 'pix',
        'data_inicio' => '2024-11-16',
        'data_fim' => '2024-11-18',
        'user_id' => $user->id,
        'servicoId' => [$servico->id],
    ]);

    $agendado = Agendado::latest()->first();

    $response = $this->putJson("/api/agendados/{$agendado->id}", [
        'data_inicio' => '2024-10-16',//ja ocupadas
        'data_fim' => '2024-10-18',//ja ocupadas
        'servicoId' => [$servico->id],
        'formapagamento' => 'pix',
    ]);

    $response->assertStatus(403);
});

it('alterar sem estar logado exibe mensagem de erro', function () {
    $user = User::factory()->create();
    $locador = User::factory()->create();
    
    $this->assertNotNull($user);
    
    $this->actingAs($user);

    $anuncio = Anuncio::factory()->create();

    $servico = Servico::factory()->create();

    $agendado = Agendado::factory()->create([
        'user_id' => $user->id,
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'pix',
        'data_inicio' => '2024-11-10',
        'data_fim' => '2024-12-10',
    ]);

    $agendado->servicos()->attach($servico->id);

    
    //o usuário está autenticado
    $this->assertTrue(Auth::check());

    Auth::logout();

    //o usuário está deslogado
    $this->assertFalse(Auth::check());

    $response = $this->getJson("/api/agendados/{$agendado->id}");
    
    $response->assertStatus(401)
    ->assertJson([
        'message' => 'Unauthenticated.',
    ]);
});

it('Excluir sem estar logado exibe mensagem de erro', function () {
    $user = User::factory()->create();
    $locador = User::factory()->create();
    
    $this->assertNotNull($user);
    
    $this->actingAs($user);

    $anuncio = Anuncio::factory()->create();

    $servico = Servico::factory()->create();

    $agendado = Agendado::factory()->create([
        'user_id' => $user->id,
        'anuncio_id' => $anuncio->id,
        'formapagamento' => 'pix',
        'data_inicio' => '2024-11-10',
        'data_fim' => '2024-12-10',
    ]);

    $agendado->servicos()->attach($servico->id); 

    
    //o usuário está autenticado
    $this->assertTrue(Auth::check());

    Auth::logout();

    //o usuário está deslogado
    $this->assertFalse(Auth::check());

    $response = $this->deleteJson("/api/agendados/{$agendado->id}");
    
    $response->assertStatus(401)
    ->assertJson([
        'message' => 'Unauthenticated.',
    ]);
});