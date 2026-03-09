<?php

return [
    /*
     * **************************************************************************
     * Default Database Connection Name
     * **************************************************************************
     *
     * Here you may specify which of the database connections below you wish
     * to use as your default connection for database operations. This is
     * the connection which will be utilized unless another connection
     * is explicitly specified when you execute a query / statement.
     *
     */

    'default' => env('DB_CONNECTION'),

    /*
     * **************************************************************************
     * Database Connections
     * **************************************************************************
     *
     * Here are each of the database connections setup for your application.
     * Of course, examples of configuring each database platform that is
     * supported by Laravel is shown below to make development simple.
     *
     *
     * All database work in Laravel is done through the PHP PDO facilities
     * so make sure you have the driver for your particular database of
     * choice installed on your machine before you begin development.
     *
     */

    'connections' => [
        'main_connection' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'database' => env('APP_ENV') === 'dev' ? env('DB_DATABASE_DEV') : env('DB_DATABASE_PROD'),
            'username' => env('APP_ENV') === 'dev' ? env('DB_USERNAME_DEV') : env('DB_USERNAME_PROD'),
            'password' => env('APP_ENV') === 'dev' ? env('DB_PASSWORD_DEV') : env('DB_PASSWORD_PROD'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],
        'trademark_connection' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'database' => env('APP_ENV') === 'dev' ? env('DB_TRADEMARK_DATABASE_DEV') : env('DB_TRADEMARKS_DATABASE_PROD'),
            'username' => env('APP_ENV') === 'dev' ? env('DB_TRADEMARK_USERNAME_DEV') : env('DB_TRADEMARKS_USERNAME_PROD'),
            'password' => env('APP_ENV') === 'dev' ? env('DB_TRADEMARK_PASSWORD_DEV') : env('DB_TRADEMARKS_PASSWORD_PROD'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],
    ],

    /*
     * **************************************************************************
     * Migration Repository Table
     * **************************************************************************
     *
     * This table keeps track of all the migrations that have already run for
     * your application. Using this information, we can determine which of
     * the migrations on disk haven't actually been run in the database.
     *
     */

    'migrations' => 'migrations',

    /*
     * **************************************************************************
     * Redis Databases
     * **************************************************************************
     *
     * Redis is an open source, fast, and advanced key-value store that also
     * provides a richer set of commands than a typical key-value systems
     * such as APC or Memcached. Laravel makes it easy to dig right in.
     *
     */

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),

        'default' => [
            'host' => env('REDIS_HOST'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
        ],
    ],

];