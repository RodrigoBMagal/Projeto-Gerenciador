<?php

namespace Tests\Unit;

use App\Models\Staff;
use App\Models\Tarefa;
use App\Services\TarefaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TarefaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_criar_lanca_excecao_para_nome_duplicado(): void
    {
        Tarefa::factory()->create(['nome' => 'Duplicada']);

        $this->expectException(ValidationException::class);

        app(TarefaService::class)->criar([
            'nome' => 'Duplicada',
            'data' => '2026-01-01',
            'descricao' => 'x',
        ]);
    }

    public function test_remover_desvincula_staff(): void
    {
        $tarefa = Tarefa::factory()->create();
        $staff = Staff::factory()->create(['tarefa_id' => $tarefa->id]);

        app(TarefaService::class)->remover($tarefa);

        $this->assertNull($staff->fresh()->tarefa_id);
        $this->assertDatabaseMissing('tarefas', ['id' => $tarefa->id]);
    }
}
