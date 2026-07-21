<?php

namespace App\Repositories\Contracts;

use App\Models\Staff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StaffRepositoryInterface
{
    public function search(?string $termo): Collection;

    /**
     * Lista paginada com filtros de busca/contrato/tarefa atribuida e ordenacao.
     */
    public function paginate(array $filtros, int $perPage, int $page): LengthAwarePaginator;

    public function find(int $id): ?Staff;

    public function findByNome(string $nome): ?Staff;

    public function create(array $dados): Staff;

    public function createMany(array $linhas): Collection;

    public function update(Staff $staff, array $dados): Staff;

    public function delete(Staff $staff): bool;

    public function comTarefaNaoNula(): Collection;

    public function desvincularTarefa(int $tarefaId): int;

    /**
     * Contagens agregadas usadas pelo dashboard: total, com/sem tarefa
     * atribuida e distribuicao por tipo de contrato.
     */
    public function estatisticas(): array;
}
