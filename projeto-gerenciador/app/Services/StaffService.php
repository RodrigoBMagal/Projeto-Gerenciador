<?php

namespace App\Services;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TarefaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Concentra a regra de negocio de Staff (equipe), incluindo a atribuicao
 * de tarefas, que antes vivia em load_table_data.php, save_table_data.php,
 * load_staff.php e update_tarefa.php.
 */
class StaffService
{
    public const CACHE_KEY_COM_TAREFA = 'staff:com_tarefa';

    public const CACHE_KEY_ESTATISTICAS = 'staff:estatisticas';

    private const CACHE_TTL_SEGUNDOS = 300;

    public function __construct(
        private readonly StaffRepositoryInterface $staff,
        private readonly TarefaRepositoryInterface $tarefas,
    ) {
    }

    public function listarPaginado(array $filtros, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->staff->paginate($filtros, $perPage, $page);
    }

    /**
     * Contagens agregadas para o dashboard (total, com/sem tarefa,
     * distribuicao por contrato). Cacheado pelo mesmo motivo que
     * TarefaService::estatisticas().
     */
    public function estatisticas(): array
    {
        return Cache::remember(self::CACHE_KEY_ESTATISTICAS, self::CACHE_TTL_SEGUNDOS, function () {
            Log::channel('structured')->info('cache_miss', ['key' => self::CACHE_KEY_ESTATISTICAS]);

            return $this->staff->estatisticas();
        });
    }

    public function buscarOuFalhar(int $id): Staff
    {
        $membro = $this->staff->find($id);

        if (! $membro) {
            throw ValidationException::withMessages([
                'staff' => 'Membro da equipe nao encontrado.',
            ]);
        }

        return $membro;
    }

    public function criar(array $dados): Staff
    {
        $membro = $this->staff->create($dados);

        $this->invalidarCache();

        return $membro;
    }

    /**
     * Cria varios registros de uma vez (equivalente ao antigo save_table_data.php,
     * que recebia um array de linhas vindo da tabela HTML).
     */
    public function criarEmLote(array $linhas): Collection
    {
        $membros = $this->staff->createMany($linhas);

        $this->invalidarCache();

        return $membros;
    }

    public function atualizar(Staff $membro, array $dados): Staff
    {
        $membro = $this->staff->update($membro, $dados);

        $this->invalidarCache();

        return $membro;
    }

    public function remover(Staff $membro): void
    {
        $this->staff->delete($membro);

        $this->invalidarCache();
    }

    /**
     * Atribui uma tarefa (pelo nome) a um membro da equipe.
     * Equivalente ao antigo update_tarefa.php, mas validando que a tarefa
     * realmente existe antes de vincular.
     */
    public function atribuirTarefa(Staff $membro, string $nomeTarefa): Staff
    {
        $tarefa = $this->tarefas->findByNome($nomeTarefa);

        if (! $tarefa) {
            throw ValidationException::withMessages([
                'tarefa_nome' => 'Tarefa informada nao existe.',
            ]);
        }

        $membro = $this->staff->update($membro, ['tarefa_id' => $tarefa->id]);

        $this->invalidarCache();

        return $membro;
    }

    /**
     * Lista membros da equipe que possuem alguma tarefa atribuida,
     * equivalente ao antigo load_staff.php.
     */
    public function comTarefaAtribuida(): Collection
    {
        return Cache::remember(self::CACHE_KEY_COM_TAREFA, self::CACHE_TTL_SEGUNDOS, function () {
            Log::channel('structured')->info('cache_miss', ['key' => self::CACHE_KEY_COM_TAREFA]);

            return $this->staff->comTarefaNaoNula();
        });
    }

    /**
     * Limpa as chaves de cache de staff. Chamado sempre que um membro e
     * criado, atualizado, removido ou tem uma tarefa (re)atribuida.
     */
    private function invalidarCache(): void
    {
        Cache::forget(self::CACHE_KEY_COM_TAREFA);
        Cache::forget(self::CACHE_KEY_ESTATISTICAS);
    }
}
