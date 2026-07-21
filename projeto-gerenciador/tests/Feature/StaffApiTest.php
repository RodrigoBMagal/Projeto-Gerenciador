<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffApiTest extends TestCase
{
    use RefreshDatabase;

    protected function autenticar(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_lista_staff_paginada(): void
    {
        $this->autenticar();
        Staff::factory(3)->create();

        $response = $this->getJson('/api/staff');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total'], 'links']);
    }

    public function test_lista_staff_com_busca(): void
    {
        $this->autenticar();
        Staff::factory()->create(['nome' => 'Joao Pereira', 'cargo' => 'Analista']);
        Staff::factory()->create(['nome' => 'Ana Souza', 'cargo' => 'Gerente']);

        $response = $this->getJson('/api/staff?search=Analista');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nome', 'Joao Pereira');
    }

    public function test_lista_staff_respeita_per_page_e_page(): void
    {
        $this->autenticar();
        Staff::factory(12)->create();

        $this->getJson('/api/staff?per_page=5&page=1')->assertOk()->assertJsonCount(5, 'data')->assertJsonPath('meta.last_page', 3);
        $this->getJson('/api/staff?per_page=5&page=3')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_filtra_staff_por_contrato(): void
    {
        $this->autenticar();
        Staff::factory(2)->create(['contrato' => 'CLT']);
        Staff::factory(3)->create(['contrato' => 'PJ']);

        $this->getJson('/api/staff?contrato=CLT')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/staff?contrato=CLT,PJ')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_filtra_staff_com_tarefa_atribuida_via_query(): void
    {
        $this->autenticar();
        $tarefa = Tarefa::factory()->create();
        Staff::factory()->create(['tarefa_id' => $tarefa->id]);
        Staff::factory()->create(['tarefa_id' => null]);

        $this->getJson('/api/staff?com_tarefa=1')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/staff?com_tarefa=0')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_ordena_staff_por_salario_descendente(): void
    {
        $this->autenticar();
        Staff::factory()->create(['nome' => 'Menor salario', 'salario' => 2000]);
        Staff::factory()->create(['nome' => 'Maior salario', 'salario' => 9000]);

        $response = $this->getJson('/api/staff?sort_by=salario&sort_dir=desc');

        $response->assertOk()->assertJsonPath('data.0.nome', 'Maior salario');
    }

    public function test_estatisticas_de_staff(): void
    {
        $this->autenticar();
        $tarefa = Tarefa::factory()->create();
        Staff::factory(2)->create(['tarefa_id' => $tarefa->id, 'contrato' => 'CLT']);
        Staff::factory(3)->create(['tarefa_id' => null, 'contrato' => 'PJ']);

        $response = $this->getJson('/api/staff/estatisticas');

        $response->assertOk()
            ->assertJsonStructure(['total', 'com_tarefa', 'sem_tarefa', 'por_contrato'])
            ->assertJsonPath('total', 5)
            ->assertJsonPath('com_tarefa', 2)
            ->assertJsonPath('sem_tarefa', 3);
    }

    public function test_cria_staff_em_lote(): void
    {
        $this->autenticar();

        $response = $this->postJson('/api/staff/lote', [
            'linhas' => [
                ['nome' => 'Carlos Lima', 'cargo' => 'Dev', 'local' => 'Rio', 'idade' => 30, 'contrato' => 'CLT', 'salario' => 8000],
                ['nome' => 'Bruna Costa', 'cargo' => 'QA', 'local' => 'SP', 'idade' => 27, 'contrato' => 'PJ', 'salario' => 7000],
            ],
        ]);

        $response->assertCreated()->assertJsonCount(2);
        $this->assertDatabaseCount('staff', 2);
    }

    public function test_atribui_tarefa_existente_a_staff(): void
    {
        $this->autenticar();
        $staff = Staff::factory()->create();
        $tarefa = Tarefa::factory()->create(['nome' => 'Fechamento mensal']);

        $response = $this->patchJson("/api/staff/{$staff->id}/tarefa", [
            'tarefa_nome' => 'Fechamento mensal',
        ]);

        $response->assertOk()->assertJsonPath('tarefa.nome', 'Fechamento mensal');
        $this->assertDatabaseHas('staff', ['id' => $staff->id, 'tarefa_id' => $tarefa->id]);
    }

    public function test_nao_atribui_tarefa_inexistente(): void
    {
        $this->autenticar();
        $staff = Staff::factory()->create();

        $response = $this->patchJson("/api/staff/{$staff->id}/tarefa", [
            'tarefa_nome' => 'Tarefa que nao existe',
        ]);

        $response->assertStatus(422);
    }

    public function test_lista_staff_com_tarefa_atribuida(): void
    {
        $this->autenticar();
        $tarefa = Tarefa::factory()->create();
        Staff::factory()->create(['tarefa_id' => $tarefa->id]);
        Staff::factory()->create(['tarefa_id' => null]);

        $response = $this->getJson('/api/staff/com-tarefa');

        $response->assertOk()->assertJsonCount(1);
    }
}
