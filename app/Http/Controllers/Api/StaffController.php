<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTarefaRequest;
use App\Http\Requests\StoreStaffBulkRequest;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\Staff;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function index(Request $request): JsonResponse
    {
        $membros = $this->service->listar($request->query('search'));

        return response()->json(StaffResource::collection($membros));
    }

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

    public function show(Staff $staff): JsonResponse
    {
        return response()->json(new StaffResource($staff->load('tarefa')));
    }

    public function update(UpdateStaffRequest $request, Staff $staff): JsonResponse
    {
        $staff = $this->service->atualizar($staff, $request->validated());

        return response()->json(new StaffResource($staff));
    }

    public function destroy(Staff $staff): JsonResponse
    {
        $this->service->remover($staff);

        return response()->json(['message' => 'Membro da equipe removido com sucesso.']);
    }

    /**
     * Atribui uma tarefa (pelo nome) a um membro da equipe.
     * Equivalente ao antigo update_tarefa.php.
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
