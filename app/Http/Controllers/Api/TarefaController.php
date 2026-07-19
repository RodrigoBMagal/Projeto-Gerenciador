<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTarefaRequest;
use App\Http\Requests\UpdateTarefaRequest;
use App\Http\Resources\TarefaResource;
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

    public function index(): JsonResponse
    {
        return response()->json(TarefaResource::collection($this->service->listar()));
    }

    public function store(StoreTarefaRequest $request): JsonResponse
    {
        $tarefa = $this->service->criar($request->validated());

        return response()->json(new TarefaResource($tarefa), 201);
    }

    public function show(Tarefa $tarefa): JsonResponse
    {
        return response()->json(new TarefaResource($tarefa));
    }

    public function update(UpdateTarefaRequest $request, Tarefa $tarefa): JsonResponse
    {
        $tarefa = $this->service->atualizar($tarefa, $request->validated());

        return response()->json(new TarefaResource($tarefa));
    }

    public function destroy(Tarefa $tarefa): JsonResponse
    {
        $this->service->remover($tarefa);

        return response()->json(['message' => 'Tarefa removida com sucesso.']);
    }
}
