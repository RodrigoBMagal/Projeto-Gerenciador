<?php

namespace App\Services;

use App\Models\Tarefa;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TarefaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Concentra toda a regra de negocio relacionada a Tarefas.
 *
 * Antes, essa logica estava espalhada em varios arquivos soltos em /php
 * (obter_tarefas.php, processar_formulario.php, verificar_nome.php,
 * apagar_item.php, update_tarefa.php). Os Controllers agora apenas
 * traduzem a requisicao HTTP e delegam para ca.
 */
class TarefaService
{
    /**
     * Chave de cache da listagem completa de tarefas (consulta mais
     * frequente da API) e seu tempo de vida.
     */
    private const CACHE_KEY_LISTA = 'tarefas:all';

    private const CACHE_TTL_SEGUNDOS = 300;

    public function __construct(
        private readonly TarefaRepositoryInterface $tarefas,
        private readonly StaffRepositoryInterface $staff,
    ) {
    }

    public function listar(): Collection
    {
        return Cache::remember(self::CACHE_KEY_LISTA, self::CACHE_TTL_SEGUNDOS, function () {
            Log::channel('structured')->info('cache_miss', ['key' => self::CACHE_KEY_LISTA]);

            return $this->tarefas->all();
        });
    }

    public function buscarOuFalhar(int $id): Tarefa
    {
        $tarefa = $this->tarefas->find($id);

        if (! $tarefa) {
            throw ValidationException::withMessages([
                'tarefa' => 'Tarefa nao encontrada.',
            ]);
        }

        return $tarefa;
    }

    public function nomeJaExiste(string $nome): bool
    {
        return $this->tarefas->nomeExiste($nome);
    }

    public function criar(array $dados): Tarefa
    {
        // Equivalente ao antigo verificar_nome.php + processar_formulario.php,
        // agora como uma unica regra de negocio consistente.
        if ($this->nomeJaExiste($dados['nome'])) {
            throw ValidationException::withMessages([
                'nome' => 'Ja existe uma tarefa com este nome.',
            ]);
        }

        $tarefa = $this->tarefas->create($dados);

        $this->invalidarCacheDeListagem();

        return $tarefa;
    }

    public function atualizar(Tarefa $tarefa, array $dados): Tarefa
    {
        if (isset($dados['nome']) && $dados['nome'] !== $tarefa->nome && $this->nomeJaExiste($dados['nome'])) {
            throw ValidationException::withMessages([
                'nome' => 'Ja existe uma tarefa com este nome.',
            ]);
        }

        $tarefa = $this->tarefas->update($tarefa, $dados);

        $this->invalidarCacheDeListagem();

        return $tarefa;
    }

    public function remover(Tarefa $tarefa): void
    {
        // Equivalente ao antigo apagar_item.php: ao remover a tarefa, desvincula
        // automaticamente qualquer membro da equipe que estivesse designado a ela.
        $this->staff->desvincularTarefa($tarefa->id);

        $this->tarefas->delete($tarefa);

        $this->invalidarCacheDeListagem();
    }

    /**
     * Limpa o cache de listagem de tarefas. Chamado sempre que uma tarefa
     * e criada, atualizada ou removida, para nunca servir dado desatualizado.
     * Tambem invalida a listagem de staff, ja que ela pode exibir a tarefa
     * associada a cada membro.
     */
    private function invalidarCacheDeListagem(): void
    {
        Cache::forget(self::CACHE_KEY_LISTA);
        Cache::forget(StaffService::CACHE_KEY_COM_TAREFA);
    }
}
