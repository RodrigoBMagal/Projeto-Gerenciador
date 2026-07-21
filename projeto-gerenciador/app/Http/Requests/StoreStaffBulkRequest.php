<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'linhas' => ['required', 'array', 'min:1'],
            'linhas.*.nome' => ['required', 'string', 'max:255'],
            'linhas.*.cargo' => ['nullable', 'string', 'max:255'],
            'linhas.*.local' => ['nullable', 'string', 'max:255'],
            'linhas.*.idade' => ['nullable', 'integer', 'min:14', 'max:120'],
            'linhas.*.contrato' => ['nullable', 'string', 'max:255'],
            'linhas.*.salario' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
