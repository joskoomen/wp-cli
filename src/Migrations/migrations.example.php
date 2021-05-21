<?php

$autoload_file = '/vendor/autoload.php';

if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . $autoload_file)) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . $autoload_file;
}
$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

return [
    'paths' => [
        'migrations' => 'database/migrations',
        'seeds' => 'database/seeds'
    ],
    'migration_base_class' => '\App\Migrations\Migration',
    'environments' => [
        'default_migration_table' => 'migrations',
        'default_database' => 'local',
        'local' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'],
            'name' => $_ENV['DB_DATABASE'],
            'user' => $_ENV['DB_USERNAME'],
            'pass' => $_ENV['DB_PASSWORD'],
            'port' => 3306
        ],
        'remote' => [
            'adapter' => 'mysql',
            'host' => $_ENV['REMOTE_DB_HOST'],
            'name' => $_ENV['REMOTE_DB_DATABASE'],
            'user' => $_ENV['REMOTE_DB_USERNAME'],
            'pass' => $_ENV['REMOTE_DB_PASSWORD'],
            'port' => 3306
        ],
    ]
];
