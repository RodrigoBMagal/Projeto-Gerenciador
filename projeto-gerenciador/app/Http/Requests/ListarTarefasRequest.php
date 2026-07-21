<?php

namespace App\Http\Requests;

use App\Models\Tarefa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida os parametros de query da listagem de tarefas:
 * busca, filtros (status/prioridade/periodo), ordenacao e paginacao.
 */
class ListarTarefasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', Rule::in(Tarefa::STATUSES)],
            'prioridade' => ['sometimes', 'nullable', Rule::in(Tarefa::PRIORIDADES)],
            'data_de' => ['sometimes', 'nullable', 'date'],
            'data_ate' => ['sometimes', 'nullable', 'date'],
            'sort_by' => ['sometimes', 'nullable', Rule::in(['nome', 'data', 'status', 'prioridade', 'created_at'])],
            'sort_dir' => ['sometimes', 'nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    public function filtros(): array
    {
        return [
            'search' => $this->validated('search'),
            'status' => $this->validated('status'),
            'prioridade' => $this->validated('prioridade'),
            'data_de' => $this->validated('data_de'),
            'data_ate' => $this->validated('data_ate'),
            'sort_by' => $this->validated('sort_by') ?? 'data',
            'sort_dir' => $this->validated('sort_dir') ?? 'asc',
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 10);
    }
}
