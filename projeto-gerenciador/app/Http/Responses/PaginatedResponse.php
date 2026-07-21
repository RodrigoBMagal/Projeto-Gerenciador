<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Monta um envelope JSON consistente para respostas paginadas:
 *
 * {
 *   "data": [...],
 *   "meta": { "current_page", "last_page", "per_page", "total", "from", "to" },
 *   "links": { "first", "last", "prev", "next" }
 * }
 *
 * Usado em vez do wrapping automatico do Laravel porque os controllers desta
 * API respondem com response()->json(...) diretamente (ver TarefaController /
 * StaffController), o que nao aciona o fluxo toResponse() que normalmente
 * adicionaria essa metadata sozinho.
 */
class PaginatedResponse
{
    public static function make(LengthAwarePaginator $paginator, string $resourceClass): array
    {
        return [
            'data' => $resourceClass::collection(collect($paginator->items())),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }
}
