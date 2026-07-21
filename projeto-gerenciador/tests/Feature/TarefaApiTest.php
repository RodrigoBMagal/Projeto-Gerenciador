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

    public function test_lista_tarefas_paginada(): void
    {
        $this->autenticar();
        Tarefa::factory(3)->create();

        $response = $this->getJson('/api/tarefas');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total'], 'links']);
    }

    public function test_lista_tarefas_respeita_per_page_e_page(): void
    {
        $this->autenticar();
        Tarefa::factory(15)->create();

        $primeiraPagina = $this->getJson('/api/tarefas?per_page=10&page=1');
        $primeiraPagina->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('meta.last_page', 2);

        $segundaPagina = $this->getJson('/api/tarefas?per_page=10&page=2');
        $segundaPagina->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_filtra_tarefas_por_status(): void
    {
        $this->autenticar();
        Tarefa::factory(2)->pendente()->create();
        Tarefa::factory(3)->concluida()->create();

        $this->getJson('/api/tarefas?status=concluida')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_filtra_tarefas_por_busca_e_periodo(): void
    {
        $this->autenticar();
        Tarefa::factory()->create(['nome' => 'Fechamento mensal', 'data' => '2026-01-10']);
        Tarefa::factory()->create(['nome' => 'Outra tarefa', 'data' => '2026-06-10']);

        $this->getJson('/api/tarefas?search=Fechamento')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Fechamento mensal');

        $this->getJson('/api/tarefas?data_de=2026-05-01&data_ate=2026-07-01')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Outra tarefa');
    }

    public function test_ordena_tarefas_por_nome_descendente(): void
    {
        $this->autenticar();
        Tarefa::factory()->create(['nome' => 'Alfa']);
        Tarefa::factory()->create(['nome' => 'Zeta']);

        $response = $this->getJson('/api/tarefas?sort_by=nome&sort_dir=desc');

        $response->assertOk()->assertJsonPath('data.0.nome', 'Zeta');
    }

    public function test_estatisticas_de_tarefas(): void
    {
        $this->autenticar();
        Tarefa::factory(2)->pendente()->create();
        Tarefa::factory(1)->emAndamento()->create();
        Tarefa::factory(3)->concluida()->create();
        Tarefa::factory(2)->atrasada()->create();

        $response = $this->getJson('/api/tarefas/estatisticas');

        $response->assertOk()
            ->assertJsonStructure(['total', 'pendente', 'em_andamento', 'concluida', 'atrasada', 'por_mes'])
            ->assertJsonPath('total', 8)
            ->assertJsonPath('concluida', 3)
            ->assertJsonPath('atrasada', 2);
    }

    public function test_cria_tarefa_com_sucesso(): void
    {
        $this->autenticar();

        $response = $this->postJson('/api/tarefas', [
            'nome' => 'Revisar contrato',
            'data' => '2026-08-01',
            'descricao' => 'Revisar clausulas do contrato com o fornecedor.',
            'status' => 'em_andamento',
            'prioridade' => 'alta',
        ]);

        $response->assertCreated()
            ->assertJsonPath('nome', 'Revisar contrato')
            ->assertJsonPath('status', 'em_andamento')
            ->assertJsonPath('prioridade', 'alta');

        $this->assertDatabaseHas('tarefas', ['nome' => 'Revisar contrato', 'status' => 'em_andamento']);
    }

    public function test_cria_tarefa_com_status_e_prioridade_padrao_quando_omitidos(): void
    {
        $this->autenticar();

        $response = $this->postJson('/api/tarefas', [
            'nome' => 'Tarefa simples',
            'data' => '2026-08-01',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'pendente')
            ->assertJsonPath('prioridade', 'media');
    }

    public function test_rejeita_status_invalido(): void
    {
        $this->autenticar();

        $this->postJson('/api/tarefas', [
            'nome' => 'Tarefa invalida',
            'data' => '2026-08-01',
            'status' => 'inexistente',
        ])->assertStatus(422)->assertJsonValidationErrors('status');
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

        $this->putJson("/api/tarefas/{$tarefa->id}", ['nome' => 'Nome Atualizado', 'status' => 'concluida'])
            ->assertOk()
            ->assertJsonPath('nome', 'Nome Atualizado')
            ->assertJsonPath('status', 'concluida');
    }

    public function test_tarefa_atrasada_e_marcada_corretamente(): void
    {
        $this->autenticar();
        $tarefa = Tarefa::factory()->atrasada()->create();

        $this->getJson("/api/tarefas/{$tarefa->id}")
            ->assertOk()
            ->assertJsonPath('atrasada', true);
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
