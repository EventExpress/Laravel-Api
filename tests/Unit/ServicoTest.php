<?php

use App\Models\Scategoria;
use App\Models\Servico;
use App\Models\TypeUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


uses(TestCase::class, RefreshDatabase::class);

test('cadastro de novo servico com todos os campos corretamente', function () {
    $this->seed(ScategoriaSeeder::class);

    $scategorias = Scategoria::all();

    $user = User::factory()->create();

    //TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locador']);

    $this->actingAs($user);
    $response = $this->postJson('/api/servicos', [
        'cidade' => 'Curitiba',
        'bairro' => 'Centro',
        'descricao' => 'Limpeza geral de casa.',
        'valor' => 150,
        'agenda' => ['data' => '2025-09-18'],
        'scategoriaId' => [$scategorias[0]->id, $scategorias[1]->id],
    ]);

    $response->assertStatus(201);
    $response->assertJson(['status' => true, 'message' => 'Serviço criado com sucesso.']);
    $this->assertDatabaseHas('servicos', ['cidade' => 'Curitiba']);
});

test('cadastro de novo servico com campo incorreto', function () {
    $this->seed(ScategoriaSeeder::class);

    $scategorias = Scategoria::all();

    $user = User::factory()->create();
    $this->actingAs($user);
    $response = $this->postJson('/api/servicos', [
        'cidade' => '',//campo vazio
        'bairro' => 'Centro',
        'descricao' => 'Limpeza geral de casa.',
        'valor' => 150,
        'agenda' => ['2024-10-15'],
        'scategoriaId' => [$scategorias[0]->id, $scategorias[1]->id],
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cidade']);
});

test('pesquisar todos os servicos oferecidos', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Criar dois serviços para o prestador
    Servico::factory()->create(['user_id' => $user->id, 'descricao' => 'Serviço de Limpeza']);
    Servico::factory()->create(['user_id' => $user->id, 'descricao' => 'Serviço de Manutenção']);

    $response = $this->getJson('/api/servicos');

    $response->assertStatus(200)
             ->assertJsonFragment(['descricao' => 'Serviço de Limpeza'])
             ->assertJsonFragment(['descricao' => 'Serviço de Manutenção']);
});

test('pesquisar por servico especifico', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $results = Servico::factory()->create(['user_id' => $user->id, 'descricao' => 'Serviço de Limpeza']);

    $response = $this->getJson("/api/servicos?search=Limpeza");

    $response->assertStatus(200)
             ->assertJsonFragment(['descricao' => 'Serviço de Limpeza']);
});

test('pesquisar servico que nao existe', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->getJson('/api/servicos?search=limpeza');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'servicos' => [],
        ]);
});


test('alterar servico com sucesso', function () {
    $this->seed(ScategoriaSeeder::class);

    $scategorias = Scategoria::all();

    $user = User::factory()->create();
    $this->actingAs($user);

    $servico = Servico::factory()->create([
        'user_id' => $user->id,
        'cidade' => 'Curitiba',
        'bairro' => 'Centro',
        'descricao' => 'Limpeza geral de casa.',
        'valor' => 150,
    ]);

    $response = $this->putJson("/api/servicos/{$servico->id}", [
        'cidade' => 'Curitiba',
        'bairro' => 'Centro',
        'descricao' => 'Manutenção geral.',
        'valor' => 200,
        'agenda' => ['data' => '2025-10-18'],
        'scategoriaId' => [$scategorias[0]->id, $scategorias[1]->id],
    ]);

    $response->assertStatus(200)
             ->assertJson(['status' => true,
              'message' => 'Serviço atualizado com sucesso.',
            ]);
});

test('alterar servico com campo vazio', function () {
    $this->seed(ScategoriaSeeder::class);

    $scategorias = Scategoria::all();
    
    $user = User::factory()->create();
    $this->actingAs($user);

    $servico = Servico::factory()->create([
        'user_id' => $user->id,
        'cidade' => 'Curitiba',
        'bairro' => 'Centro',
        'descricao' => 'Limpeza geral de casa.',
        'valor' => 150,
    ]);

    $response = $this->putJson("/api/servicos/{$servico->id}", [
        'cidade' => '',//campo vazio
        'bairro' => 'Centro',
        'descricao' => 'Manutenção geral.',
        'valor' => 200,
        'agenda' => ['data' => '2025-10-18'],
        'scategoriaId' => [$scategorias[0]->id, $scategorias[1]->id],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cidade']);
});

test('excluir servico com sucesso', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $servico = Servico::factory()->create(['user_id' => $user->id]);

    $response = $this->deleteJson("/api/servicos/{$servico->id}");

    $response->assertStatus(200)
             ->assertJson(['status' => true,
              'message' => 'Serviço excluído com sucesso.']);
});

test('tentar excluir servico inexistente', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->deleteJson('/api/servicos{id}');

    $response->assertStatus(404)
             ->assertJson(['message' => 'The route api/servicos{id} could not be found.']);
});
