<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida os parametros de query da listagem de staff:
 * busca, filtro por contrato/tarefa atribuida, ordenacao e paginacao.
 */
class ListarStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contrato' => ['sometimes', 'nullable', 'string', 'max:255'],
            'com_tarefa' => ['sometimes', 'nullable', 'boolean'],
            'sort_by' => ['sometimes', 'nullable', Rule::in(['nome', 'cargo', 'local', 'idade', 'contrato', 'salario', 'created_at'])],
            'sort_dir' => ['sometimes', 'nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    public function filtros(): array
    {
        return [
            'search' => $this->validated('search'),
            // Aceita "CLT,PJ" (multiplo, usado pelos filtros do front) ou um unico valor.
            'contrato' => $this->validated('contrato')
                ? array_filter(array_map('trim', explode(',', $this->validated('contrato'))))
                : null,
            'com_tarefa' => $this->has('com_tarefa') ? $this->boolean('com_tarefa') : null,
            'sort_by' => $this->validated('sort_by') ?? 'nome',
            'sort_dir' => $this->validated('sort_dir') ?? 'asc',
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 10);
    }
}
