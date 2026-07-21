<?php

namespace App\Repositories\Eloquent;

use App\Models\Tarefa;
use App\Repositories\Contracts\TarefaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TarefaRepository implements TarefaRepositoryInterface
{
    public function all(): Collection
    {
        return Tarefa::query()->orderBy('data')->get();
    }

    public function paginate(array $filtros, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->aplicarFiltros(Tarefa::query(), $filtros)
            ->orderBy($filtros['sort_by'] ?? 'data', $filtros['sort_dir'] ?? 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function aplicarFiltros(Builder $query, array $filtros): Builder
    {
        return $query
            ->when($filtros['search'] ?? null, function (Builder $q, string $termo) {
                $q->where(function (Builder $sub) use ($termo) {
                    $sub->where('nome', 'like', "%{$termo}%")
                        ->orWhere('descricao', 'like', "%{$termo}%");
                });
            })
            ->when($filtros['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filtros['prioridade'] ?? null, fn (Builder $q, string $prioridade) => $q->where('prioridade', $prioridade))
            ->when($filtros['data_de'] ?? null, fn (Builder $q, string $de) => $q->whereDate('data', '>=', $de))
            ->when($filtros['data_ate'] ?? null, fn (Builder $q, string $ate) => $q->whereDate('data', '<=', $ate));
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

    public function estatisticas(): array
    {
        $porStatus = Tarefa::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $atrasadas = Tarefa::query()
            ->where('status', '!=', Tarefa::STATUS_CONCLUIDA)
            ->whereDate('data', '<', now()->startOfDay())
            ->count();

        // A funcao de formatacao de data varia entre drivers (SQLite usa
        // strftime, MySQL usa DATE_FORMAT) — os testes rodam em SQLite e a
        // aplicacao em producao roda em MySQL (ver config/database.php).
        $expressaoMes = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', data)"
            : "DATE_FORMAT(data, '%Y-%m')";

        $porMes = Tarefa::query()
            ->select(DB::raw("{$expressaoMes} as mes"), DB::raw('count(*) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes');

        return [
            'total' => array_sum($porStatus->all()),
            'pendente' => (int) ($porStatus[Tarefa::STATUS_PENDENTE] ?? 0),
            'em_andamento' => (int) ($porStatus[Tarefa::STATUS_EM_ANDAMENTO] ?? 0),
            'concluida' => (int) ($porStatus[Tarefa::STATUS_CONCLUIDA] ?? 0),
            'atrasada' => $atrasadas,
            'por_mes' => $porMes->toArray(),
        ];
    }
}
