<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da tarefa e obrigatorio.',
            'data.required' => 'A data da tarefa e obrigatoria.',
            'data.date' => 'A data informada e invalida.',
        ];
    }
}
