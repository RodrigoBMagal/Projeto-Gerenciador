<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Staff */
class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'cargo' => $this->cargo,
            'local' => $this->local,
            'idade' => $this->idade,
            'contrato' => $this->contrato,
            'salario' => $this->salario,
            'tarefa' => new TarefaResource($this->whenLoaded('tarefa')),
            'tarefa_id' => $this->tarefa_id,
        ];
    }
}
