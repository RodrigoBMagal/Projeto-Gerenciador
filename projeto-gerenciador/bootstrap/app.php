<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\LogApiRequests;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Equivalente ao antigo TrimStrings::$except do app/Http/Kernel.php
        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $middleware->alias([
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
        ]);

        // 'api' ja vem com ThrottleRequests + SubstituteBindings por padrao no Laravel 12.
        $middleware->throttleApi();
        $middleware->api(append: [
            LogApiRequests::class,
        ]);

        // Mantido para paridade com o Sanctum::EnsureFrontendRequestsAreStateful
        // que estava no grupo 'api' do Kernel original (auth via SPA/cookies).
        // Se a API so usa tokens Bearer, este helper pode ser removido sem problema.
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Padroniza toda resposta de erro da API em JSON (equivalente ao
        // antigo app/Exceptions/Handler.php).
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Os dados informados sao invalidos.',
                    'errors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Erro ao processar a requisicao.',
                ], $e->getStatusCode());
            }

            if (config('app.debug')) {
                return null; // deixa o Laravel/Ignition mostrar detalhes em ambiente local
            }

            return response()->json([
                'message' => 'Erro interno no servidor.',
            ], 500);
        });
    })->create();
