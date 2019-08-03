<?php
if(file_exists(__DIR__.'/.env')) {
    $dotenv = Dotenv\Dotenv::create(__DIR__.'/');
    $dotenv->overload();
}

return [
    'paths' => [
        'migrations' => 'db/migrations',
        'seeds' => 'db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_database' => 'development',
        'production' => [
            'adapter' => 'pgsql',
            'host' => getenv('DB_HOST'),
            'name' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'pass' => getenv('DB_PASSWD'),
            'port' => 5432,
            'charset' => 'utf8'
        ],
        'development' => [
            'adapter' => 'pgsql',
            'host' => getenv('DB_HOST'),
            'name' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'pass' => getenv('DB_PASSWD'),
            'port' => 5432,
            'charset' => 'utf8'
        ]
    ],
    'version_order' => 'creation'
];
