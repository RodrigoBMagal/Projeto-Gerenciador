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

    public function test_lista_staff_com_busca(): void
    {
        $this->autenticar();
        Staff::factory()->create(['nome' => 'Joao Pereira', 'cargo' => 'Analista']);
        Staff::factory()->create(['nome' => 'Ana Souza', 'cargo' => 'Gerente']);

        $response = $this->getJson('/api/staff?search=Analista');

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.nome', 'Joao Pereira');
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
