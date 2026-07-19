<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TarefaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function autenticar(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_lista_tarefas(): void
    {
        $this->autenticar();
        Tarefa::factory(3)->create();

        $this->getJson('/api/tarefas')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_cria_tarefa_com_sucesso(): void
    {
        $this->autenticar();

        $response = $this->postJson('/api/tarefas', [
            'nome' => 'Revisar contrato',
            'data' => '2026-08-01',
            'descricao' => 'Revisar clausulas do contrato com o fornecedor.',
        ]);

        $response->assertCreated()->assertJsonPath('nome', 'Revisar contrato');

        $this->assertDatabaseHas('tarefas', ['nome' => 'Revisar contrato']);
    }

    public function test_nao_permite_criar_tarefa_com_nome_duplicado(): void
    {
        $this->autenticar();
        Tarefa::factory()->create(['nome' => 'Tarefa Unica']);

        $response = $this->postJson('/api/tarefas', [
            'nome' => 'Tarefa Unica',
            'data' => '2026-08-01',
            'descricao' => 'Outra descricao',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('nome');
    }

    public function test_valida_campos_obrigatorios_ao_criar_tarefa(): void
    {
        $this->autenticar();

        $this->postJson('/api/tarefas', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'data']);
    }

    public function test_atualiza_tarefa(): void
    {
        $this->autenticar();
        $tarefa = Tarefa::factory()->create();

        $this->putJson("/api/tarefas/{$tarefa->id}", ['nome' => 'Nome Atualizado'])
            ->assertOk()
            ->assertJsonPath('nome', 'Nome Atualizado');
    }

    public function test_remover_tarefa_desvincula_staff_associado(): void
    {
        $this->autenticar();
        $tarefa = Tarefa::factory()->create();
        $staff = Staff::factory()->create(['tarefa_id' => $tarefa->id]);

        $this->deleteJson("/api/tarefas/{$tarefa->id}")->assertOk();

        $this->assertDatabaseMissing('tarefas', ['id' => $tarefa->id]);
        $this->assertDatabaseHas('staff', ['id' => $staff->id, 'tarefa_id' => null]);
    }
}
