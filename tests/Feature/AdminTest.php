<?php 

use App\Models\Servico;
use App\Models\Anuncio;
use App\Models\User;
use App\Models\TypeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Http\UploadedFile; 

uses(RefreshDatabase::class);


/*test('excluir serviço com sucesso', function () {
    // Criação de um administrador
    $admin = User::factory()->create();
    TypeUser::create(['user_id' => $admin->id, 'tipousu' => 'admin']);

    // Criação de um usuário "prestador" que vai criar o serviço
    $user = User::factory()->create();
    TypeUser::create(['user_id' => $user->id, 'tipousu' => 'prestador']);

    // Autenticar como o prestador para criar o serviço
    Sanctum::actingAs($user);

    // O usuário prestador cria o serviço
    $response = $this->postJson('/api/servicos', [
        'titulo' => 'Serviço de Limpeza',
        'cidade' => 'Curitiba',
        'bairro' => 'Centro',
        'descricao' => 'Limpeza geral de casa.',
        'valor' => 150,
        'agenda' => '2024-10-15',
    ]);

    $response->assertStatus(201)
             ->assertJson(['message' => 'Serviço criado com sucesso.']);

    $servico = Servico::latest()->first(); // Recuperar o serviço criado
    $this->assertNotNull($servico, 'O serviço não foi encontrado após a criação.');

    // Autenticar como admin para excluir o serviço
    Sanctum::actingAs($admin);

    // O admin exclui o serviço
    $response = $this->deleteJson("/admin/servico/{$servico->id}");

    // Verificações
    $response->assertStatus(200)
             ->assertJson(['message' => 'Serviço excluído com sucesso.']);

    // Verifica se o serviço foi excluído do banco de dados
    $this->assertDeleted('servicos', ['id' => $servico->id]);
});*/