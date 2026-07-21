<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\TarefaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Todas as rotas aqui ja recebem o prefixo /api (ver RouteServiceProvider).
*/

// Health-check publico, usado por Docker/Kubernetes/monitoramento externo
Route::get('/health', HealthController::class);

// Rotas publicas de autenticacao
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas (exigem token Sanctum: Authorization: Bearer {token})
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Tarefas
    Route::get('/tarefas/estatisticas', [TarefaController::class, 'estatisticas']);
    Route::apiResource('tarefas', TarefaController::class)->except(['show'])->parameters(['tarefas' => 'tarefa']);
    Route::get('/tarefas/{tarefa}', [TarefaController::class, 'show']);

    // Staff (equipe)
    Route::get('/staff/estatisticas', [StaffController::class, 'estatisticas']);
    Route::get('/staff/com-tarefa', [StaffController::class, 'comTarefa']);
    Route::post('/staff/lote', [StaffController::class, 'storeBulk']);
    Route::patch('/staff/{staff}/tarefa', [StaffController::class, 'atribuirTarefa']);
    Route::apiResource('staff', StaffController::class);
});
