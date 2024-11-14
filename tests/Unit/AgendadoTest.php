<?php

use App\Models\Categoria;
use App\Models\Endereco;
use App\Models\Agendado;
use App\Models\Anuncio;
use App\Models\Servico;
use App\Models\TypeUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile; 

uses(TestCase::class, RefreshDatabase::class);

test('incluir dados incompletos no formulario de reserva', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

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

    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Anúncio criado com sucesso.',
             ]);

    $anuncio = Anuncio::latest()->first();

    $servico = Servico::factory()->create();

    $response = $this->postJson("/api/agendados/{$anuncio->id}", [
        'servicoId' => [$servico->id], 
        'formapagamento' => '', // campo vazio
        'data_inicio' => '2024-12-10',
        'data_fim' => '2024-12-11',
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2024-12-10',
                'data_fim' => '2024-12-11',
            ]
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['formapagamento']);
});

test('criar uma reserva corretamente', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

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

    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Anúncio criado com sucesso.',
             ]);

    $anuncio = Anuncio::latest()->first();


    $servico = Servico::factory()->create();

    $response = $this->postJson("/api/agendados/{$anuncio->id}", [
        'servicoId' => [$servico->id],
        'formapagamento' => 'pix',
        'data_inicio' => '2024-12-10',
        'data_fim' => '2024-12-11',
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2024-12-10',
                'data_fim' => '2024-12-11',
            ]
        ],
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
        'data_inicio' => '2024-12-10',
        'data_fim' => '2024-12-11',
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2024-12-10',
                'data_fim' => '2024-12-11',
            ]
        ],
    ]);

    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Reserva criada com sucesso.',
             ]);
    
    $agendado = Agendado::latest()->first();

    $response = $this->putJson("/api/agendados/{$agendado->id}", [
        'data_inicio' => '2024-11-20',
        'data_fim' => '2024-12-11',
        'servicoId' => [$servico->id],
        'formapagamento' => 'pix',
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2024-11-20',
                'data_fim' => '2024-12-11',
            ]
        ],
    ]);

    $response->assertStatus(200)
             ->assertJson([
                 'status' => true,
                 'message' => 'Reserva atualizada com sucesso.',
             ]);
});

test('atualizar reserva sem sucesso', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

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
        'data_inicio' => '2024-12-10',
        'data_fim' => '2024-12-11',
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2024-12-10',
                'data_fim' => '2024-12-11',
            ]
        ],
    ]);

    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Reserva criada com sucesso.',
             ]);
    
    $agendado = Agendado::latest()->first();

    $response = $this->putJson("/api/agendados/{$agendado->id}", [
        'formapagamento' => 'pix',
        'data_inicio' => '2024-12-20',
        'data_fim' => '',
        'servicoId' => [$servico->id],
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2024-12-20',
                'data_fim' => '',
            ]
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data_fim']);
});

test('tentar editar reserva fora do prazo permitido', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $locador = User::factory()->create();
    $this->seed(CategoriaSeeder::class);
    $categorias = Categoria::all();
    
    $imagens = [
        base64_encode(UploadedFile::fake()->image('imagem1.jpg')->getContent()),
        base64_encode(UploadedFile::fake()->image('imagem2.jpg')->getContent()),
    ];
    $this->postJson('/api/anuncios', [
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
    
    $this->postJson("/api/agendados/{$anuncio->id}", [
        'servicoId' => [$servico->id],
        'formapagamento' => 'pix',
        'data_inicio' => now()->addDays(1)->toDateString(), // Reserva para daqui 1 dias
        'data_fim' => now()->addDays(2)->toDateString(),
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => now()->addDays(1)->toDateString(),
                'data_fim' => now()->addDays(2)->toDateString(),
            ]
        ],
    ]);

    $agendado = Agendado::latest()->first();

    $response = $this->putJson("/api/agendados/{$agendado->id}", [
        'data_inicio' => '2024-10-10',
        'data_fim' => '2024-10-11',
        'servicoId' => [$servico->id],
        'formapagamento' => 'pix',
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2024-10-10',
                'data_fim' => '2024-10-11',
            ]
        ],
    ]);

    $response->assertStatus(403)
             ->assertJson([
                 'status' => false,
                 'error' => 'Você só pode editar esta reserva até 3 dias antes da data de início.',
             ]);
});

test('Pesquisar reserva futura', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

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
        'data_inicio' => '2024-11-10',
        'data_fim' => '2024-12-10',
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2024-11-10',
                'data_fim' => '2024-12-10',
            ]
        ],
    ]);

    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Reserva criada com sucesso.',
             ]);

    $response = $this->getJson("/api/agendados/{$anuncio->id}?search=2024-11-10");

    $response->assertStatus(200)
             ->assertJson(['status' => true])
             ->assertJsonFragment(['data_inicio' => '2024-11-10 00:00:00']);
});

test('Pesquisar reserva passada', function () {
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
        'servico_id' => [$servico->id],
        'formapagamento' => 'pix',
        'data_inicio' => '2023-10-10', // Reserva passada
        'data_fim' => '2023-11-10',    // Reserva passada
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2023-10-10',
                'data_fim' => '2024-11-10',
            ]
        ],
        
    ]);

    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Reserva criada com sucesso.',
             ]);

    $response = $this->getJson("/api/agendados/{$anuncio->id}?search=2023-10-10");
    $response->assertStatus(200)
             ->assertJson(['status' => true])
             ->assertJsonFragment(['data_inicio' => '2023-10-10 00:00:00']);
});

test('cancelar reserva com sucesso', function () {
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
        'servico_id' => [$servico->id],
        'formapagamento' => 'pix',
        'data_inicio' => '2024-12-10',
        'data_fim' => '2024-12-11',
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => '2024-12-10',
                'data_fim' => '2024-12-11',
            ]
        ],
    ]);

    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Reserva criada com sucesso.',
             ]);
    
    $agendado = Agendado::latest()->first();

    $response = $this->deleteJson("/api/agendados/{$agendado->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'status' => true,
                 'message' => 'Reserva excluída com sucesso.',
             ]);
});

test('tentar cancelar reserva fora do prazo permitido', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $locador = User::factory()->create();
    $this->seed(CategoriaSeeder::class);
    $categorias = Categoria::all();
    
    $imagens = [
        base64_encode(UploadedFile::fake()->image('imagem1.jpg')->getContent()),
        base64_encode(UploadedFile::fake()->image('imagem2.jpg')->getContent()),
    ];
    $this->postJson('/api/anuncios', [
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
    
    $this->postJson("/api/agendados/{$anuncio->id}", [
        'servicoId' => [$servico->id],
        'formapagamento' => 'pix',
        'data_inicio' => now()->addDays(2)->toDateString(), // Reserva para daqui 3 dias
        'data_fim' => now()->addDays(3)->toDateString(),
        'servicos_data' => [
            [
                'id' => $servico->id,
                'data_inicio' => now()->addDays(2)->toDateString(),
                'data_fim' => now()->addDays(3)->toDateString(),
            ]
        ],
    ]);

    $agendado = Agendado::latest()->first();

    $response = $this->deleteJson("/api/agendados/{$agendado->id}");

    $response->assertStatus(403)
             ->assertJson([
                 'status' => false,
                 'error' => 'Você só pode cancelar esta reserva até 3 dias antes da data de início.',
             ]);
});

test('tentar cancelar reserva inexistente', function () {
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