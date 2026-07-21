<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_se_registrar(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'senha-forte-123',
            'password_confirmation' => 'senha-forte-123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
    }

    public function test_usuario_pode_fazer_login_com_credenciais_validas(): void
    {
        $user = User::factory()->create(['password' => bcrypt('senha-123')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'senha-123',
        ]);

        $response->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_falha_com_credenciais_invalidas(): void
    {
        $user = User::factory()->create(['password' => bcrypt('senha-123')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'senha-errada',
        ]);

        $response->assertStatus(422);
    }

    public function test_rota_protegida_exige_autenticacao(): void
    {
        $this->getJson('/api/tarefas')->assertStatus(401);
    }

    public function test_usuario_autenticado_acessa_proprio_perfil(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/me');

        $response->assertOk()->assertJsonPath('email', $user->email);
    }
}
