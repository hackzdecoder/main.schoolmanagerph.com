<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\RelationNotFoundException;

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
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Unauthorized Access'
            ], 401);
        });

        // Handle Class Not Found Errors
        $exceptions->render(function (\Error $e) {
            // Check if it's a class not found error
            if (str_contains($e->getMessage(), 'Class "')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Internal Server Error',
                    'message' => config('app.debug') ? $e->getMessage() : 'A server error occurred. Please try again later.'
                ], 500);
            }

            // For other PHP errors
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        });

        // Handle Database Query Exception
        $exceptions->render(function (QueryException $e) {
            // Check if it's a connection error
            if (str_contains($e->getMessage(), 'SQLSTATE[HY000] [2002]')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }

            // For other database errors
            return new JsonResponse([
                'success' => false,
                'response' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        });

        // Handle Relation Not Found Exception
        $exceptions->render(function (RelationNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid relationship requested',
                'message' => config('app.debug') ? $e->getMessage() : 'The requested relationship does not exist'
            ], 400);
        });
    })->create();