<?php 

use App\Http\Controllers\AdminController;
use App\Http\Middeleware\AdminAccess;
use App\Models\Servico;
use App\Models\Anuncio;
use App\Models\User;
use App\Models\TypeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Http\UploadedFile; 

uses(RefreshDatabase::class);


it('deletar serviço como admin', function () {
    
    $this->seed(TypeUserSeeder::class);

    $admin = User::factory()->create();
    $typeAdmin = TypeUser::where('tipousu', 'admin')->first();
    
    $admin->typeUsers()->attach($typeAdmin->id);

    $prestador = User::factory()->create();

    $servico = Servico::factory()->create(['user_id'=> $prestador->id]);

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/admin/servicos/{$servico->id}");

    $response->assertStatus(200)
             ->assertJson(['message' => 'Serviço deletado com sucesso!']);

    $this->assertSoftDeleted('servicos', ['id' => $servico->id]);
});


it('deletar anuncio como admin', function () {
    
    $this->seed(TypeUserSeeder::class);

    $admin = User::factory()->create();
    $typeAdmin = TypeUser::where('tipousu', 'admin')->first();
    
    $admin->typeUsers()->attach($typeAdmin->id);

    $locador = User::factory()->create();

    $anuncio = Anuncio::factory()->create(['user_id'=> $locador->id]);

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/admin/anuncios/{$anuncio->id}");

    $response->assertStatus(200)
             ->assertJson(['message' => 'Anúncio deletado com sucesso!']);
});


it('deletar usuário como admin', function () {
    $this->seed(TypeUserSeeder::class);

    $admin = User::factory()->create();
    $typeAdmin = TypeUser::where('tipousu', 'admin')->first();
    
    $admin->typeUsers()->attach($typeAdmin->id);

    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/admin/user/{$user->id}");
    $response->assertStatus(200);
    $response->assertJson(['message' => 'Usuário deletado com sucesso!']);
    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

it('deletar e restaurar usuário como admin', function () {
    $this->seed(TypeUserSeeder::class);

    $admin = User::factory()->create();
    $typeAdmin = TypeUser::where('tipousu', 'admin')->first();
    
    $admin->typeUsers()->attach($typeAdmin->id);

    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/admin/user/{$user->id}");
    $response->assertStatus(200);
    $response->assertJson(['message' => 'Usuário deletado com sucesso!']);
    $this->assertSoftDeleted('users', ['id' => $user->id]);

    $restoreResponse = $this->patchJson("/api/admin/user/restore/{$user->id}");
    $restoreResponse->assertStatus(200)->assertJson(['message' => 'Usuário restaurado com sucesso!']);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);

});

/*it('deletar e restaurar anuncio como admin', function () {
    $this->seed(TypeUserSeeder::class);

    $admin = User::factory()->create();
    $typeAdmin = TypeUser::where('tipousu', 'admin')->first();
    $admin->typeUsers()->attach($typeAdmin->id);

    $locador = User::factory()->create();

    $anuncio = Anuncio::factory()->create(['user_id'=> $locador->id]);

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/admin/anuncios/{$anuncio->id}");

    $response->assertStatus(200)
             ->assertJson(['message' => 'Anúncio deletado com sucesso!']);

    $restoreResponse = $this->patchJson("/api/admin/anuncios/restore/{$anuncio->id}");
    $restoreResponse->assertStatus(200)->assertJson(['message' => 'Anúncio restaurado com sucesso!']);
    $this->assertDatabaseHas('anuncios', ['id' => $anuncio->id, 'deleted_at' => null]);

});*/

it('deletar e restaurar servico como admin', function () {
    $this->seed(TypeUserSeeder::class);

    $admin = User::factory()->create();
    $typeAdmin = TypeUser::where('tipousu', 'admin')->first();
    $admin->typeUsers()->attach($typeAdmin->id);

    $prestador = User::factory()->create();

    $servico = Servico::factory()->create(['user_id'=> $prestador->id]);

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/admin/servicos/{$servico->id}");

    $response->assertStatus(200)
             ->assertJson(['message' => 'Serviço deletado com sucesso!']);
    $this->assertSoftDeleted('servicos', ['id' => $servico->id]);

    $restoreResponse = $this->patchJson("/api/admin/servicos/restore/{$servico->id}");
    $restoreResponse->assertStatus(200)->assertJson(['message' => 'Serviço restaurado com sucesso!']);
    $this->assertDatabaseHas('servicos', ['id' => $servico->id, 'deleted_at' => null]);

});