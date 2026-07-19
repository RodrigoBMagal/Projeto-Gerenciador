<?php

namespace App\Repositories\Eloquent;

use App\Models\Tarefa;
use App\Repositories\Contracts\TarefaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TarefaRepository implements TarefaRepositoryInterface
{
    public function all(): Collection
    {
        return Tarefa::query()->orderBy('data')->get();
    }

    public function find(int $id): ?Tarefa
    {
        return Tarefa::query()->find($id);
    }

    public function findByNome(string $nome): ?Tarefa
    {
        return Tarefa::query()->where('nome', $nome)->first();
    }

    public function nomeExiste(string $nome): bool
    {
        return Tarefa::query()->where('nome', $nome)->exists();
    }

    public function create(array $dados): Tarefa
    {
        return Tarefa::query()->create($dados);
    }

    public function update(Tarefa $tarefa, array $dados): Tarefa
    {
        $tarefa->update($dados);

        return $tarefa->refresh();
    }

    public function delete(Tarefa $tarefa): bool
    {
        return (bool) $tarefa->delete();
    }
}
