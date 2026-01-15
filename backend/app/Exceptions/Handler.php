<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        return $this->customApiResponse($exception);
    }

    protected function customApiResponse(Throwable $exception)
    {
        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => 'Os dados fornecidos são inválidos.',
                'errors' => $exception->errors(),
            ], 422);
        }

        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        }

        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Recurso não encontrado.',
            ], 404);
        }

        // Log do erro para diagnóstico
        \Log::error('Erro interno no servidor', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $isDebug = config('app.debug', false);
        
        // Verificar se é erro de configuração comum
        $errorMessage = $exception->getMessage();
        $hint = null;
        
        if (str_contains($errorMessage, 'APP_KEY') || str_contains($errorMessage, 'No application encryption key')) {
            $hint = 'APP_KEY não configurada. Execute: php artisan key:generate';
        } elseif (str_contains($errorMessage, 'database') || str_contains($errorMessage, 'SQLSTATE')) {
            $hint = 'Erro de conexão com banco de dados. Verifique as credenciais no .env';
        } elseif (str_contains($errorMessage, 'cache') || str_contains($errorMessage, 'config')) {
            $hint = 'Cache desatualizado. Execute: php artisan config:clear && php artisan cache:clear';
        }

        return response()->json([
            'message' => 'Erro interno no servidor.',
            'error' => $isDebug ? $exception->getMessage() : null,
            'hint' => $hint,
            'file' => $isDebug ? $exception->getFile() . ':' . $exception->getLine() : null,
        ], 500);
    }
}
