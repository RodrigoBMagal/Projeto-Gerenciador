<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Loga cada requisicao da API em formato estruturado (JSON), incluindo
 * metodo, rota, status, tempo de resposta e usuario autenticado (se houver).
 * Pensado para ser consumido por ferramentas de observabilidade
 * (ELK, Datadog, CloudWatch, Grafana Loki, etc.).
 */
class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $inicio = microtime(true);
        $requestId = (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);

        /** @var Response $response */
        $response = $next($request);

        $duracaoMs = round((microtime(true) - $inicio) * 1000, 2);

        $nivel = $response->getStatusCode() >= 500 ? 'error'
            : ($response->getStatusCode() >= 400 ? 'warning' : 'info');

        Log::channel('structured')->{$nivel}('api_request', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duracaoMs,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
