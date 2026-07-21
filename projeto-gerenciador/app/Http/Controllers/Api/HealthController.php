<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Endpoint de health-check para uso por ferramentas de monitoramento
 * (Docker healthcheck, Kubernetes liveness/readiness probe, UptimeRobot,
 * Datadog synthetics, etc.).
 */
class HealthController extends Controller
{
    /**
     * Verifica a saude da aplicacao e suas dependencias (banco e cache).
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $saudavel = ! in_array(false, array_column($checks, 'ok'), true);

        $payload = [
            'status' => $saudavel ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ];

        if (! $saudavel) {
            Log::channel('structured')->warning('health_check_failed', $payload);
        }

        return response()->json($payload, $saudavel ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        $inicio = microtime(true);

        try {
            DB::select('select 1');

            return ['ok' => true, 'latency_ms' => $this->latencia($inicio)];
        } catch (Throwable $e) {
            return ['ok' => false, 'latency_ms' => $this->latencia($inicio), 'error' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        $inicio = microtime(true);

        try {
            Cache::put('health:check', true, 5);
            $ok = Cache::get('health:check') === true;

            return ['ok' => $ok, 'latency_ms' => $this->latencia($inicio)];
        } catch (Throwable $e) {
            return ['ok' => false, 'latency_ms' => $this->latencia($inicio), 'error' => $e->getMessage()];
        }
    }

    private function latencia(float $inicio): float
    {
        return round((microtime(true) - $inicio) * 1000, 2);
    }
}
