<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Campos que nunca devem ser incluidos em mensagens de erro.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Padroniza toda resposta de erro da API em JSON, mesmo excecoes nao tratadas
        // explicitamente pelos controllers (ex: model not found, validacao, etc.).
        $this->renderable(function (Throwable $e, Request $request) {
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
    }
}
