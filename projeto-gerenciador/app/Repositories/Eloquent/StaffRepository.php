<?php

namespace App\Repositories\Eloquent;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StaffRepository implements StaffRepositoryInterface
{
    public function search(?string $termo): Collection
    {
        return $this->aplicarBusca(Staff::query(), $termo)
            ->orderBy('nome')
            ->get();
    }

    public function paginate(array $filtros, int $perPage, int $page): LengthAwarePaginator
    {
        $query = $this->aplicarBusca(Staff::query()->with('tarefa'), $filtros['search'] ?? null);

        if (! empty($filtros['contrato'])) {
            $query->whereIn('contrato', (array) $filtros['contrato']);
        }

        if (array_key_exists('com_tarefa', $filtros) && $filtros['com_tarefa'] !== null) {
            $filtros['com_tarefa'] ? $query->whereNotNull('tarefa_id') : $query->whereNull('tarefa_id');
        }

        return $query
            ->orderBy($filtros['sort_by'] ?? 'nome', $filtros['sort_dir'] ?? 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function aplicarBusca(Builder $query, ?string $termo): Builder
    {
        return $query->when($termo, function (Builder $q) use ($termo) {
            $q->where(function (Builder $sub) use ($termo) {
                $sub->where('nome', 'like', "%{$termo}%")
                    ->orWhere('cargo', 'like', "%{$termo}%")
                    ->orWhere('local', 'like', "%{$termo}%")
                    ->orWhere('idade', 'like', "%{$termo}%")
                    ->orWhere('contrato', 'like', "%{$termo}%")
                    ->orWhere('salario', 'like', "%{$termo}%");
            });
        });
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

    public function estatisticas(): array
    {
        $total = Staff::query()->count();
        $comTarefa = Staff::query()->whereNotNull('tarefa_id')->count();

        $porContrato = Staff::query()
            ->select(DB::raw("COALESCE(contrato, 'Nao informado') as contrato"), DB::raw('count(*) as total'))
            ->groupBy('contrato')
            ->pluck('total', 'contrato');

        return [
            'total' => $total,
            'com_tarefa' => $comTarefa,
            'sem_tarefa' => $total - $comTarefa,
            'por_contrato' => $porContrato->toArray(),
        ];
    }
}
