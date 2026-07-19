<?php

namespace App\Repositories\Contracts;

use App\Models\Tarefa;
use Illuminate\Database\Eloquent\Collection;

interface TarefaRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Tarefa;

    public function findByNome(string $nome): ?Tarefa;

    public function nomeExiste(string $nome): bool;

    public function create(array $dados): Tarefa;

    public function update(Tarefa $tarefa, array $dados): Tarefa;

    public function delete(Tarefa $tarefa): bool;
}
