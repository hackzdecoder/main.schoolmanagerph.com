<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\JsonResponse;
use App\Helpers\Utilities;

class DatabaseManagerServiceProvider extends ServiceProvider
{
    /**
     * Register application services
     */
    public function register(): void
    {
        $this->app->singleton('database.manager', function ($app) {
            return $this;
        });
    }

    /**
     * Bootstrap application services
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get database connection
     * @return \Illuminate\Database\Connection|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public static function connection()
    {
        try {
            return DB::connection('main_connection');
        } catch (\Throwable $th) {
            return new JsonResponse([
                'status' => 'error',
                'response' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get database source data for current authenticated user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public static function source()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return new JsonResponse([
                    'status' => 'error',
                    'response' => 'Unauthorized access'
                ], 401);
            }

            $userAccessData = $user->toArray();

            $databaseName = Utilities::setSchoolDatabase(
                $userAccessData,
                config('app.env')
            );

            if (!$databaseName) {
                return new JsonResponse([
                    'status' => 'error',
                    'response' => 'Database configuration failed'
                ], 404);
            }

            // Verify database exists
            $result = DB::connection('main_connection')
                ->select(
                    "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?",
                    [$databaseName]
                );

            $schemaExists = isset($result[0]->SCHEMA_NAME);

            // Make connection config
            $connectionConfig = [
                'driver' => 'mysql',
                'host' => config('database.connections.main_connection.host'),
                'port' => config('database.connections.main_connection.port'),
                'database' => $databaseName,
                'username' => config('database.connections.main_connection.username'),
                'password' => config('database.connections.main_connection.password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ];

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'user_id' => $userAccessData['user_id'],
                    'environment' => config('app.env'),
                    'database' => $databaseName,
                    'exists' => $schemaExists,
                    'connection_config' => $connectionConfig
                ]
            ], 200);

        } catch (\Throwable $th) {
            return new JsonResponse([
                'status' => 'error',
                'response' => $th->getMessage()
            ], 500);
        }
    }
}