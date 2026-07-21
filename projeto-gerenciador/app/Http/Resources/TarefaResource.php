<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Tarefa */
class TarefaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'data' => $this->data?->format('Y-m-d'),
            'descricao' => $this->descricao,
            'status' => $this->status,
            'prioridade' => $this->prioridade,
            'atrasada' => $this->estaAtrasada(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
