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

        // DATABASE QUERY EXCEPTIONS - COMPLETE WITH SPECIFIC MESSAGES
        $exceptions->render(function (QueryException $e) {
            $message = $e->getMessage();
            $errorCode = $e->getCode();

            // For development - show everything
            if (config('app.debug')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Database Error',
                    'message' => $message,
                    'error_code' => $errorCode,
                    'sql' => $e->getSql() ?? null,
                    'bindings' => $e->getBindings() ?? null,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }

            // For production - user-friendly specific messages
    
            // CONNECTION ERRORS (HY000)
            if (str_contains($message, 'SQLSTATE[HY000] [1045]')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Database Authentication Failed',
                    'message' => 'Invalid database username or password. Please check your database credentials.'
                ], 500);
            }

            if (str_contains($message, 'SQLSTATE[HY000] [1044]')) {
                // Extract database name from error
                $dbName = 'the database';
                if (preg_match("/database '([^']+)'/", $message, $matches)) {
                    $dbName = $matches[1];
                }
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Database Access Denied',
                    'message' => "User does not have permission to access '{$dbName}'. Please check database privileges."
                ], 500);
            }

            if (str_contains($message, 'SQLSTATE[HY000] [1049]')) {
                // Extract database name from error
                $dbName = 'the database';
                if (preg_match("/database '([^']+)'/", $message, $matches)) {
                    $dbName = $matches[1];
                }
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Database Not Found',
                    'message' => "Database '{$dbName}' does not exist. Please create it first."
                ], 500);
            }

            if (str_contains($message, 'SQLSTATE[HY000] [2002]')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Connection Refused',
                    'message' => 'Cannot connect to database server. Please check if MySQL is running and host/port are correct.'
                ], 500);
            }

            if (str_contains($message, 'SQLSTATE[HY000] [2003]')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'MySQL Server Unreachable',
                    'message' => 'MySQL server is not responding. Please check your database server.'
                ], 500);
            }

            if (str_contains($message, 'SQLSTATE[HY000] [1040]')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Too Many Connections',
                    'message' => 'Database has too many connections. Please try again later.'
                ], 503);
            }

            // TABLE ERRORS (42S02)
            if (str_contains($message, 'SQLSTATE[42S02]')) {
                // Extract table name from error
                $tableName = 'the table';
                if (preg_match("/Table '(?:.*?)\\.?([^']+)' doesn't exist/", $message, $matches)) {
                    $tableName = $matches[1];
                } else if (preg_match("/Table '([^']+)' doesn't exist/", $message, $matches)) {
                    $tableName = $matches[1];
                }
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Table Not Found',
                    'message' => "Table '{$tableName}' does not exist in the database."
                ], 500);
            }

            // COLUMN ERRORS (42S22)
            if (str_contains($message, 'SQLSTATE[42S22]')) {
                // Extract column name from error
                $columnName = 'unknown column';
                if (preg_match("/column '([^']+)'/i", $message, $matches)) {
                    $columnName = $matches[1];
                }
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Column Not Found',
                    'message' => "Column '{$columnName}' does not exist in the table."
                ], 500);
            }

            // DUPLICATE ENTRY ERRORS (23000)
            if (str_contains($message, 'SQLSTATE[23000]') && str_contains($message, 'Duplicate entry')) {
                // Extract duplicate value and key
                $value = 'unknown value';
                $key = 'unknown key';
                if (preg_match("/Duplicate entry '([^']+)' for key '([^']+)'/", $message, $matches)) {
                    $value = $matches[1];
                    $key = $matches[2];
                }
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Duplicate Entry',
                    'message' => "A record with '{$value}' already exists. Please use a different value."
                ], 409);
            }

            // FOREIGN KEY CONSTRAINT ERRORS
            if (str_contains($message, 'SQLSTATE[23000]') && str_contains($message, 'foreign key constraint')) {
                if (str_contains($message, 'Cannot delete')) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Cannot Delete',
                        'message' => 'Cannot delete this record because it is referenced by other records.'
                    ], 409);
                }
                if (str_contains($message, 'Cannot add')) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Invalid Reference',
                        'message' => 'The referenced record does not exist.'
                    ], 409);
                }
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Foreign Key Constraint',
                    'message' => 'Database constraint violation.'
                ], 409);
            }

            // NULL VALUE ERRORS
            if (str_contains($message, 'SQLSTATE[23000]') && str_contains($message, 'cannot be null')) {
                // Extract column name
                $column = 'a field';
                if (preg_match("/column '([^']+)' cannot be null/i", $message, $matches)) {
                    $column = $matches[1];
                }
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Required Field Missing',
                    'message' => "{$column} cannot be empty."
                ], 422);
            }

            // DATA TOO LONG ERRORS
            if (str_contains($message, 'SQLSTATE[22001]')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Data Too Long',
                    'message' => 'The provided data exceeds the maximum allowed length.'
                ], 422);
            }

            // LOCK WAIT TIMEOUT
            if (str_contains($message, 'SQLSTATE[HY000] [1205]')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Lock Timeout',
                    'message' => 'Database operation timed out due to lock. Please try again.'
                ], 503);
            }

            // DEADLOCK ERRORS
            if (str_contains($message, 'SQLSTATE[40001]')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Deadlock Detected',
                    'message' => 'Transaction deadlock occurred. Please try again.'
                ], 503);
            }

            // SYNTAX ERRORS
            if (str_contains($message, 'SQLSTATE[42000]')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'SQL Syntax Error',
                    'message' => 'There is an error in the database query syntax.'
                ], 500);
            }

            // DEFAULT CATCH-ALL
            return new JsonResponse([
                'success' => false,
                'error' => 'Database Error',
                'message' => 'A database error occurred. Please try again later.'
            ], 500);
        });

        // Relation Not Found Exception
        $exceptions->render(function (RelationNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid Relationship',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'The requested relationship does not exist'
            ], 400);
        });

        // Model Not Found Exception
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Resource Not Found',
                'message' => 'The requested item does not exist'
            ], 404);
        });

        // Class/Type Errors
        $exceptions->render(function (\Error $e) {
            if (str_contains($e->getMessage(), 'Class "')) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Internal Server Error',
                    'message' => config('app.debug')
                        ? $e->getMessage()
                        : 'A server configuration error occurred'
                ], 500);
            }

            return new JsonResponse([
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'An unexpected error occurred'
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
                    : 'An unexpected error occurred'
            ], $statusCode);
        });
    })->create();