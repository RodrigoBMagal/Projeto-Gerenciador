<?php

namespace App\Repositories\Contracts;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Collection;

interface StaffRepositoryInterface
{
    public function search(?string $termo): Collection;

    public function find(int $id): ?Staff;

    public function findByNome(string $nome): ?Staff;

    public function create(array $dados): Staff;

    public function createMany(array $linhas): Collection;

    public function update(Staff $staff, array $dados): Staff;

    public function delete(Staff $staff): bool;

    public function comTarefaNaoNula(): Collection;

    public function desvincularTarefa(int $tarefaId): int;
}
