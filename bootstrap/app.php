<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Authentication Errors
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Unauthorized Access'
            ], 401);
        });

        // Validation Errors
        $exceptions->render(function (ValidationException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        });

        // Not Found HTTP Errors
        $exceptions->render(function (NotFoundHttpException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Resource not found'
            ], 404);
        });

        // Database Query Exceptions
        $exceptions->render(function (QueryException $e) {
            $message = config('app.debug')
                ? $e->getMessage()
                : 'Database error occurred';

            // Check for specific database errors
            if (str_contains($e->getMessage(), 'SQLSTATE[HY000] [1045]')) {
                $message = config('app.debug')
                    ? $e->getMessage()
                    : 'Database connection failed - invalid credentials';
            }

            if (str_contains($e->getMessage(), 'SQLSTATE[HY000] [1049]')) {
                $message = config('app.debug')
                    ? $e->getMessage()
                    : 'Database not found';
            }

            if (str_contains($e->getMessage(), 'SQLSTATE[42S02]')) {
                $message = config('app.debug')
                    ? $e->getMessage()
                    : 'Table not found';
            }

            return new JsonResponse([
                'success' => false,
                'error' => 'Database Error',
                'message' => $message,

                'debug' => config('app.debug') ? [
                    'sql' => $e->getSql() ?? null,
                    'bindings' => $e->getBindings() ?? null,
                    'code' => $e->getCode()
                ] : null
            ], 500);
        });

        // Relation Not Found Exception
        $exceptions->render(function (RelationNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid relationship',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'The requested relationship does not exist'
            ], 400);
        });

        // Model Not Found Exception
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Resource not found',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'The requested item does not exist'
            ], 404);
        });

        // Class/Type Errors
        $exceptions->render(function (\Error $e) {
            // Class not found specific handling
            if (str_contains($e->getMessage(), 'Class "')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Internal Server Error',
                    'message' => config('app.debug')
                        ? $e->getMessage()
                        : 'A server error occurred',
                    'type' => 'class_not_found'
                ], 500);
            }

            // Other PHP errors
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'An unexpected error occurred',
                'type' => 'php_error'
            ], 500);
        });

        // Catch-all for any other exceptions
        $exceptions->render(function (\Throwable $e) {
            $statusCode = method_exists($e, 'getStatusCode')
                ? $e->getStatusCode()
                : 500;

            return new JsonResponse([
                'success' => false,
                'error' => 'Server Error',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'An unexpected error occurred',
                'file' => config('app.debug') ? $e->getFile() : null,
                'line' => config('app.debug') ? $e->getLine() : null,
                'type' => get_class($e)
            ], $statusCode);
        });
    })->create();