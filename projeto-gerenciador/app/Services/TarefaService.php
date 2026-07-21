<?php

namespace App\Services;

use App\Models\Tarefa;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TarefaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * A listagem paginada tem alta cardinalidade de chaves (pagina + busca +
     * filtros + ordenacao), entao NAO e cacheada — o cache e reservado para
     * a consulta agregada de estatisticas, que e a mesma para todo mundo e
     * consultada a cada carregamento do dashboard.
     */
    public const CACHE_KEY_ESTATISTICAS = 'tarefas:estatisticas';

    private const CACHE_TTL_SEGUNDOS = 300;

    public function __construct(
        private readonly TarefaRepositoryInterface $tarefas,
        private readonly StaffRepositoryInterface $staff,
    ) {
    }

    public function listarPaginado(array $filtros, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->tarefas->paginate($filtros, $perPage, $page);
    }

    /**
     * Contagens agregadas para o dashboard (total, por status, atrasadas e
     * distribuicao mensal). Cacheado por ser consultado a cada carregamento
     * do dashboard e ser identico para todos os usuarios.
     */
    public function estatisticas(): array
    {
        return Cache::remember(self::CACHE_KEY_ESTATISTICAS, self::CACHE_TTL_SEGUNDOS, function () {
            Log::channel('structured')->info('cache_miss', ['key' => self::CACHE_KEY_ESTATISTICAS]);

            return $this->tarefas->estatisticas();
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

        $this->invalidarCache();

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

        $this->invalidarCache();

        return $tarefa;
    }

    public function remover(Tarefa $tarefa): void
    {
        // Equivalente ao antigo apagar_item.php: ao remover a tarefa, desvincula
        // automaticamente qualquer membro da equipe que estivesse designado a ela.
        $this->staff->desvincularTarefa($tarefa->id);

        $this->tarefas->delete($tarefa);

        $this->invalidarCache();
    }

    /**
     * Limpa os caches afetados por uma mudanca em tarefas: as proprias
     * estatisticas de tarefas e as de staff (que contam quem tem tarefa
     * atribuida) e a listagem "staff com tarefa".
     */
    private function invalidarCache(): void
    {
        Cache::forget(self::CACHE_KEY_ESTATISTICAS);
        Cache::forget(StaffService::CACHE_KEY_COM_TAREFA);
        Cache::forget(StaffService::CACHE_KEY_ESTATISTICAS);
    }
}
