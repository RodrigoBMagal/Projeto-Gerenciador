<?php

namespace App\Repositories\Eloquent;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StaffRepository implements StaffRepositoryInterface
{
    public function search(?string $termo): Collection
    {
        return Staff::query()
            ->when($termo, function ($query) use ($termo) {
                $query->where(function ($q) use ($termo) {
                    $q->where('nome', 'like', "%{$termo}%")
                        ->orWhere('cargo', 'like', "%{$termo}%")
                        ->orWhere('local', 'like', "%{$termo}%")
                        ->orWhere('idade', 'like', "%{$termo}%")
                        ->orWhere('contrato', 'like', "%{$termo}%")
                        ->orWhere('salario', 'like', "%{$termo}%");
                });
            })
            ->orderBy('nome')
            ->get();
    }

    public function find(int $id): ?Staff
    {
        return Staff::query()->find($id);
    }

    public function findByNome(string $nome): ?Staff
    {
        return Staff::query()->where('nome', $nome)->first();
    }

    public function create(array $dados): Staff
    {
        return Staff::query()->create($dados);
    }

    public function createMany(array $linhas): Collection
    {
        $criados = collect($linhas)->map(fn (array $linha) => Staff::query()->create($linha));

        return new Collection($criados->all());
    }

    public function update(Staff $staff, array $dados): Staff
    {
        $staff->update($dados);

        return $staff->refresh();
    }

    public function delete(Staff $staff): bool
    {
        return (bool) $staff->delete();
    }

    public function comTarefaNaoNula(): Collection
    {
        return Staff::query()->with('tarefa')->whereNotNull('tarefa_id')->get();
    }

    public function desvincularTarefa(int $tarefaId): int
    {
        return Staff::query()->where('tarefa_id', $tarefaId)->update(['tarefa_id' => null]);
    }
}
