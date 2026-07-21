<?php

namespace App\Http\Requests;

use App\Models\Tarefa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTarefaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'data' => ['required', 'date'],
            'descricao' => ['nullable', 'string'],
            'status' => ['sometimes', 'nullable', Rule::in(Tarefa::STATUSES)],
            'prioridade' => ['sometimes', 'nullable', Rule::in(Tarefa::PRIORIDADES)],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da tarefa e obrigatorio.',
            'data.required' => 'A data da tarefa e obrigatoria.',
            'data.date' => 'A data informada e invalida.',
            'status.in' => 'Status invalido. Use pendente, em_andamento ou concluida.',
            'prioridade.in' => 'Prioridade invalida. Use baixa, media ou alta.',
        ];
    }
}
