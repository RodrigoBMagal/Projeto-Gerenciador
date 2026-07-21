<?php

namespace App\Repositories\Contracts;

use App\Models\Tarefa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TarefaRepositoryInterface
{
    public function all(): Collection;

    /**
     * Lista paginada com filtros de busca/status/prioridade/periodo e ordenacao.
     */
    public function paginate(array $filtros, int $perPage, int $page): LengthAwarePaginator;

    public function find(int $id): ?Tarefa;

    public function findByNome(string $nome): ?Tarefa;

    public function nomeExiste(string $nome): bool;

    public function create(array $dados): Tarefa;

    public function update(Tarefa $tarefa, array $dados): Tarefa;

    public function delete(Tarefa $tarefa): bool;

    /**
     * Contagens agregadas usadas pelo dashboard: total, por status,
     * atrasadas e distribuicao por mes (com base na coluna `data`).
     */
    public function estatisticas(): array;
}
