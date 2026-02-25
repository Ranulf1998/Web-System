<?php

return [
    'database_prefix' => env('TENANCY_DATABASE_PREFIX', 'tenant_'),
    'charset' => env('TENANCY_DB_CHARSET', 'utf8mb4'),
    'collation' => env('TENANCY_DB_COLLATION', 'utf8mb4_unicode_ci'),

    'tenant_host' => env('TENANT_DB_HOST', env('DB_HOST', '127.0.0.1')),
    'tenant_port' => env('TENANT_DB_PORT', env('DB_PORT', '3306')),
    'tenant_username' => env('TENANT_DB_USERNAME', env('DB_USERNAME', 'root')),
    'tenant_password' => env('TENANT_DB_PASSWORD', env('DB_PASSWORD', '')),
];
