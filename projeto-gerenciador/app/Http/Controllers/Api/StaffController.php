<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTarefaRequest;
use App\Http\Requests\ListarStaffRequest;
use App\Http\Requests\StoreStaffBulkRequest;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Http\Responses\PaginatedResponse;
use App\Models\Staff;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint REST de Staff (equipe). Nao contem regra de negocio: apenas
 * traduz a requisicao HTTP e delega para StaffService.
 *
 * Substitui: load_table_data.php, save_table_data.php, load_staff.php
 * e update_tarefa.php.
 */
class StaffController extends Controller
{
    public function __construct(private readonly StaffService $service)
    {
    }

    /**
     * Lista/pesquisa membros da equipe de forma paginada.
     *
     * Aceita os parametros de query: search, contrato (um valor ou varios
     * separados por virgula, ex. "CLT,PJ"), com_tarefa (true|false),
     * sort_by (nome|cargo|local|idade|contrato|salario|created_at),
     * sort_dir (asc|desc), per_page (max. 100) e page.
     */
    public function index(ListarStaffRequest $request): JsonResponse
    {
        $paginator = $this->service->listarPaginado(
            $request->filtros(),
            $request->perPage(),
            (int) $request->query('page', 1)
        );

        return response()->json(PaginatedResponse::make($paginator, StaffResource::class));
    }

    /**
     * Contagens agregadas para o dashboard: total, com/sem tarefa
     * atribuida e distribuicao por tipo de contrato.
     */
    public function estatisticas(): JsonResponse
    {
        return response()->json($this->service->estatisticas());
    }

    /**
     * Cria um novo membro da equipe.
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        $membro = $this->service->criar($request->validated());

        return response()->json(new StaffResource($membro), 201);
    }

    /**
     * Cadastro em lote, equivalente ao antigo save_table_data.php,
     * que recebia todas as linhas da tabela HTML de uma vez.
     */
    public function storeBulk(StoreStaffBulkRequest $request): JsonResponse
    {
        $membros = $this->service->criarEmLote($request->validated('linhas'));

        return response()->json(StaffResource::collection($membros), 201);
    }

    /**
     * Exibe um membro especifico da equipe.
     */
    public function show(Staff $staff): JsonResponse
    {
        return response()->json(new StaffResource($staff->load('tarefa')));
    }

    /**
     * Atualiza os dados de um membro da equipe.
     */
    public function update(UpdateStaffRequest $request, Staff $staff): JsonResponse
    {
        $staff = $this->service->atualizar($staff, $request->validated());

        return response()->json(new StaffResource($staff));
    }

    /**
     * Remove um membro da equipe.
     */
    public function destroy(Staff $staff): JsonResponse
    {
        $this->service->remover($staff);

        return response()->json(['message' => 'Membro da equipe removido com sucesso.']);
    }

    /**
     * Atribui uma tarefa (pelo nome) a um membro da equipe.
     *
     * Falha com 422 caso a tarefa informada nao exista.
     */
    public function atribuirTarefa(AssignTarefaRequest $request, Staff $staff): JsonResponse
    {
        $staff = $this->service->atribuirTarefa($staff, $request->validated('tarefa_nome'));

        return response()->json(new StaffResource($staff->load('tarefa')));
    }

    /**
     * Lista membros da equipe que possuem tarefa atribuida.
     * Equivalente ao antigo load_staff.php.
     */
    public function comTarefa(): JsonResponse
    {
        $membros = $this->service->comTarefaAtribuida();

        return response()->json(StaffResource::collection($membros));
    }
}
