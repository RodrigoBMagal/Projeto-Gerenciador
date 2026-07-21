<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListarTarefasRequest;
use App\Http\Requests\StoreTarefaRequest;
use App\Http\Requests\UpdateTarefaRequest;
use App\Http\Resources\TarefaResource;
use App\Http\Responses\PaginatedResponse;
use App\Models\Tarefa;
use App\Services\TarefaService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint REST de Tarefas. Nao contem regra de negocio: apenas
 * traduz a requisicao HTTP e delega para TarefaService.
 *
 * Substitui: obter_tarefas.php, get_tarefa.php, processar_formulario.php,
 * verificar_nome.php e apagar_item.php.
 */
class TarefaController extends Controller
{
    public function __construct(private readonly TarefaService $service)
    {
    }

    /**
     * Lista tarefas de forma paginada.
     *
     * Aceita os parametros de query: search, status (pendente|em_andamento|
     * concluida), prioridade (baixa|media|alta), data_de, data_ate,
     * sort_by (nome|data|status|prioridade|created_at), sort_dir (asc|desc),
     * per_page (max. 100) e page.
     */
    public function index(ListarTarefasRequest $request): JsonResponse
    {
        $paginator = $this->service->listarPaginado(
            $request->filtros(),
            $request->perPage(),
            (int) $request->query('page', 1)
        );

        return response()->json(PaginatedResponse::make($paginator, TarefaResource::class));
    }

    /**
     * Contagens agregadas para o dashboard: total, por status, atrasadas
     * e distribuicao mensal de tarefas cadastradas.
     */
    public function estatisticas(): JsonResponse
    {
        return response()->json($this->service->estatisticas());
    }

    /**
     * Cria uma nova tarefa.
     *
     * Falha com 422 caso ja exista uma tarefa com o mesmo nome.
     */
    public function store(StoreTarefaRequest $request): JsonResponse
    {
        $tarefa = $this->service->criar($request->validated());

        return response()->json(new TarefaResource($tarefa), 201);
    }

    /**
     * Exibe uma tarefa especifica.
     */
    public function show(Tarefa $tarefa): JsonResponse
    {
        return response()->json(new TarefaResource($tarefa));
    }

    /**
     * Atualiza uma tarefa existente.
     */
    public function update(UpdateTarefaRequest $request, Tarefa $tarefa): JsonResponse
    {
        $tarefa = $this->service->atualizar($tarefa, $request->validated());

        return response()->json(new TarefaResource($tarefa));
    }

    /**
     * Remove uma tarefa.
     *
     * Qualquer membro da equipe vinculado a essa tarefa e automaticamente
     * desvinculado (tarefa_id passa a null).
     */
    public function destroy(Tarefa $tarefa): JsonResponse
    {
        $this->service->remover($tarefa);

        return response()->json(['message' => 'Tarefa removida com sucesso.']);
    }
}
