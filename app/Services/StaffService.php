<?php

namespace App\Services;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TarefaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Concentra a regra de negocio de Staff (equipe), incluindo a atribuicao
 * de tarefas, que antes vivia em load_table_data.php, save_table_data.php,
 * load_staff.php e update_tarefa.php.
 */
class StaffService
{
    public function __construct(
        private readonly StaffRepositoryInterface $staff,
        private readonly TarefaRepositoryInterface $tarefas,
    ) {
    }

    public function listar(?string $termo): Collection
    {
        return $this->staff->search($termo);
    }

    public function buscarOuFalhar(int $id): Staff
    {
        $membro = $this->staff->find($id);

        if (! $membro) {
            throw ValidationException::withMessages([
                'staff' => 'Membro da equipe nao encontrado.',
            ]);
        }

        return $membro;
    }

    public function criar(array $dados): Staff
    {
        return $this->staff->create($dados);
    }

    /**
     * Cria varios registros de uma vez (equivalente ao antigo save_table_data.php,
     * que recebia um array de linhas vindo da tabela HTML).
     */
    public function criarEmLote(array $linhas): Collection
    {
        return $this->staff->createMany($linhas);
    }

    public function atualizar(Staff $membro, array $dados): Staff
    {
        return $this->staff->update($membro, $dados);
    }

    public function remover(Staff $membro): void
    {
        $this->staff->delete($membro);
    }

    /**
     * Atribui uma tarefa (pelo nome) a um membro da equipe.
     * Equivalente ao antigo update_tarefa.php, mas validando que a tarefa
     * realmente existe antes de vincular.
     */
    public function atribuirTarefa(Staff $membro, string $nomeTarefa): Staff
    {
        $tarefa = $this->tarefas->findByNome($nomeTarefa);

        if (! $tarefa) {
            throw ValidationException::withMessages([
                'tarefa_nome' => 'Tarefa informada nao existe.',
            ]);
        }

        return $this->staff->update($membro, ['tarefa_id' => $tarefa->id]);
    }

    /**
     * Lista membros da equipe que possuem alguma tarefa atribuida,
     * equivalente ao antigo load_staff.php.
     */
    public function comTarefaAtribuida(): Collection
    {
        return $this->staff->comTarefaNaoNula();
    }
}
