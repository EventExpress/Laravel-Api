<?php

use App\Models\ImagemAnuncio;
use App\Models\Endereco;
use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\TypeUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile; 

uses(TestCase::class, RefreshDatabase::class);


test('cadastro de novo anuncio com todos os campos corretamente', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();

    $user = User::factory()->create();

    //autentica o usuário
    $this->actingAs($user);


    // Simular imagens em Base64
    $imagens = [
        base64_encode(UploadedFile::fake()->image('imagem1.jpg')->getContent()),
        base64_encode(UploadedFile::fake()->image('imagem2.jpg')->getContent()),
    ];


    $response = $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => ['2025-09-18'],
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
        'imagens' => $imagens,
        
    ]);
    
    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Anúncio criado com sucesso.',
             ]);
});



test('preencher campos obrigatórios incorretamente', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();

    $user = User::factory()->create();
    //TypeUser::create(['user_id' => $user->id, 'tipousu' => 'locador']);
    $this->actingAs($user);

    $imagens = [
        base64_encode(UploadedFile::fake()->image('imagem1.jpg')->getContent()),
        base64_encode(UploadedFile::fake()->image('imagem2.jpg')->getContent()),
    ];

    $response = $this->postJson('/api/anuncios', [
        'titulo' => '', //campo vazio
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => ['data' => '2025-09-18'],
        'categoriaId' =>[$categorias[0]->id, $categorias[1]->id],
        'imagens' => $imagens
    ]);

    $response->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => [
                     'titulo',
                 ],
             ]);
});


test('pesquisar anuncio com termos válidos', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();

    $user = User::factory()->create();
    $this->actingAs($user);

    $imagens = [
        base64_encode(UploadedFile::fake()->image('imagem1.jpg')->getContent()),
        base64_encode(UploadedFile::fake()->image('imagem2.jpg')->getContent()),
    ];

    $response = $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => ['data' => '2025-09-18'],
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
        'imagens' => $imagens
    ]);

    $response->assertStatus(201)
             ->assertJson([
                 'status' => true,
                 'message' => 'Anúncio criado com sucesso.',
             ]);

    $response = $this->getJson('/api/anuncios?search=Festa');
    $response->assertStatus(200)
             ->assertJson(['status' => true])
             ->assertJsonFragment(['titulo' => 'Festa de Casamento']);
});



test('tentar pesquisar anuncio com termos inválidos', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();

    $user = User::factory()->create();
    $this->actingAs($user);

    $imagens = [
        base64_encode(UploadedFile::fake()->image('imagem1.jpg')->getContent()),
        base64_encode(UploadedFile::fake()->image('imagem2.jpg')->getContent()),
    ];

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => ['data' => '2025-09-18'],
        'categoriaId' => [1, 2],
        'imagens' => $imagens
    ]);

    $response = $this->getJson('/api/anuncios?search=Aniversario');

    $response->assertStatus(200)
             ->assertJson([
                 'status' => true,
                 'anuncios' => [],
             ]);
});

test('pesquisar todos os anuncios', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();

    $user = User::factory()->create();
    $this->actingAs($user);
    
    $imagens = [
        base64_encode(UploadedFile::fake()->image('imagem1.jpg')->getContent()),
        base64_encode(UploadedFile::fake()->image('imagem2.jpg')->getContent()),
    ];

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => ['data' => '2025-09-18'],
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
        'imagens' => $imagens
    ]);

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Aniversário',
        'cidade' => 'Curitiba',
        'cep' => '12345-679',
        'numero' => 456,
        'bairro' => 'Batel',
        'capacidade' => 200,
        'descricao' => 'Um local perfeito para festas de aniversário.',
        'valor' => 3000,
        'agenda' => ['data' => '2025-09-18'],
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
        'imagens' => $imagens
    ]);

    $response = $this->getJson('/api/anuncios');

    $response->assertStatus(200)
        ->assertJsonFragment(['titulo' => 'Festa de Casamento'])
        ->assertJsonFragment(['titulo' => 'Festa de Aniversário'])
        ->assertJson([
            'status' => true,
        ]);
});

test('pesquisar por anuncio especifico', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();

    $user = User::factory()->create();
    $this->actingAs($user);

    $imagens = [
        base64_encode(UploadedFile::fake()->image('imagem1.jpg')->getContent()),
        base64_encode(UploadedFile::fake()->image('imagem2.jpg')->getContent()),
    ];

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => ['data' => '2025-09-18'],
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
        'imagens' => $imagens
    ]);

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Aniversário',
        'cidade' => 'Curitiba',
        'cep' => '12345-679',
        'numero' => 456,
        'bairro' => 'Batel',
        'capacidade' => 200,
        'descricao' => 'Um local perfeito para festas de aniversário.',
        'valor' => 3000,
        'agenda' => ['data' => '2025-09-18'],
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
        'imagens' => $imagens
    ]);

    $response = $this->getJson('/api/anuncios?search=Casamento');

    $response->assertStatus(200)
        ->assertJsonFragment(['titulo' => 'Festa de Casamento'])
        ->assertJson([
            'status' => true,
        ]);
});

test('tentar pesquisar por anuncio inexistente', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();

    $user = User::factory()->create();
    $this->actingAs($user);


    $imagens = [
        base64_encode(UploadedFile::fake()->image('imagem1.jpg')->getContent()),
        base64_encode(UploadedFile::fake()->image('imagem2.jpg')->getContent()),
    ];

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Um local perfeito para festas de casamento.',
        'valor' => 2000,
        'agenda' => ['data' => '2025-09-18'],
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
        'imagens' => $imagens
    ]);

    $this->postJson('/api/anuncios', [
        'titulo' => 'Festa de Aniversário',
        'cidade' => 'Curitiba',
        'cep' => '12345-679',
        'numero' => 456,
        'bairro' => 'Batel',
        'capacidade' => 200,
        'descricao' => 'Um local perfeito para festas de aniversário.',
        'valor' => 3000,
        'agenda' => ['data' => '2025-09-18'],
        'categoriaId' => [1,2],
        'imagens' => $imagens
    ]);

    $response = $this->getJson('/api/anuncios?search=infantil');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'anuncios' => [],
        ]);
});


test('alterar anuncio com sucesso', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();

    $user = User::factory()->create();
    Sanctum::actingAs($user); // Autentica o usuário com Sanctum

    $endereco = Endereco::factory()->create([
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 456,
        'bairro' => 'Batel',
    ]);

    $anuncio = Anuncio::factory()->create([
        'user_id' => $user->id,
        'endereco_id' => $endereco->id,
        'titulo' => 'Festa de Aniversário',
        'capacidade' => 50,
        'descricao' => 'Local ideal para festas de aniversário.',
        'valor' => 1500,
    ]);

    // Atualiza o anúncio com os campos de endereço diretamente no payload
    $response = $this->putJson("/api/anuncios/{$anuncio->id}", [
        'titulo' => 'Festa de Casamento',
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Local perfeito para festas de casamento.',
        'valor' => 2000,
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
    ]);

    $response->assertStatus(200)
             ->assertJson([
                 'status' => true,
                 'message' => 'Anúncio atualizado com sucesso.',
             ]);
});

test('alterar anuncio sem sucesso', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();

    $user = User::factory()->create();
    Sanctum::actingAs($user); 
    
    $imagens = [
        base64_encode(UploadedFile::fake()->image('imagem1.jpg')->getContent()),
        base64_encode(UploadedFile::fake()->image('imagem2.jpg')->getContent()),
    ];

    $endereco = Endereco::factory()->create([
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 456,
        'bairro' => 'Batel',
    ]);

    $anuncio = Anuncio::factory()->create([
        'user_id' => $user->id,
        'endereco_id' => $endereco->id,
        'titulo' => 'Festa de Aniversário',
        'capacidade' => 50,
        'descricao' => 'Local ideal para festas de aniversário.',
        'valor' => 1500,
    ]);

    // Salvar as imagens separadamente
    foreach ($imagens as $imagemBase64) {
        ImagemAnuncio::create([
            'anuncio_id' => $anuncio->id,
            'image_path' => $imagemBase64,
            'is_main' => false,
    ]);
    }


    $response = $this->putJson("/api/anuncios/{$anuncio->id}", [
        'titulo' => '',//campo vazio
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 123,
        'bairro' => 'Portão',
        'capacidade' => 100,
        'descricao' => 'Local perfeito para festas de casamento.',
        'valor' => 2000,
        'categoriaId' => [$categorias[0]->id, $categorias[1]->id],
        'imagens' => $imagens
    ]);

   $response->assertStatus(422);

   $response->assertJsonValidationErrors(['titulo']);

   $response->assertJson([
       'message' => 'Erro de validação.',
       ]);
});


test('excluir anuncio com sucesso', function () {
    $this->seed(CategoriaSeeder::class);

    $categorias = Categoria::all();

    $user = User::factory()->create();
    Sanctum::actingAs($user); 

    $endereco = Endereco::factory()->create([
        'cidade' => 'Curitiba',
        'cep' => '12345-678',
        'numero' => 456,
        'bairro' => 'Batel',
    ]);

    $anuncio = Anuncio::factory()->create([
        'user_id' => $user->id,
        'endereco_id' => $endereco->id,
        'titulo' => 'Festa de Aniversário',
        'capacidade' => 50,
        'descricao' => 'Local ideal para festas de aniversário.',
        'valor' => 1500,
    ]);

    $response = $this->deleteJson("/api/anuncios/{$anuncio->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'status' => true,
                 'message' => 'Anúncio excluído com sucesso.',
             ]);
    $this->assertSoftDeleted('anuncios', [
                'id' => $anuncio->id,
    ]);

});

test('tentar excluir anuncio inexistente', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $inexistenteId = 9999; // ID arbitrário que não corresponde a nenhum anúncio

    $response = $this->deleteJson("/api/anuncios/{$inexistenteId}");

    $response->assertStatus(403)
             ->assertJson([
                'status' => false,
                'error' => 'Anúncio não encontrado ou você não tem permissão para excluí-lo.',
             ]);
});

