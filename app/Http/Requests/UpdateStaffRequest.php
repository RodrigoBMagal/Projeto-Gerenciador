<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'local' => ['nullable', 'string', 'max:255'],
            'idade' => ['nullable', 'integer', 'min:14', 'max:120'],
            'contrato' => ['nullable', 'string', 'max:255'],
            'salario' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
