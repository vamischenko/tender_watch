<?php

declare(strict_types=1);

return [
    'application' => require __DIR__ . '/application.php',

    'yiisoft/aliases' => [
        'aliases' => require __DIR__ . '/aliases.php',
    ],

    'app' => [
        'name' => 'TenderWatch',
        'version' => '1.0.0',
    ],

    'db' => [
        'dsn' => 'pgsql:host=' . (getenv('DB_HOST') ?: 'postgres') . ';dbname=' . (getenv('DB_NAME') ?: 'tender_watch') . ';port=' . (getenv('DB_PORT') ?: '5432'),
        'username' => getenv('DB_USER') ?: 'app',
        'password' => getenv('DB_PASSWORD') ?: 'secret',
    ],

    'redis' => [
        'host' => getenv('REDIS_HOST') ?: 'redis',
        'port' => (int)(getenv('REDIS_PORT') ?: 6379),
        'database' => 0,
    ],

    'telegram' => [
        'bot_token' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
        'webhook_secret' => getenv('TELEGRAM_WEBHOOK_SECRET') ?: '',
    ],

    'mailer' => [
        'dsn' => getenv('MAILER_DSN') ?: 'smtp://localhost:1025',
        'from' => getenv('MAILER_FROM') ?: 'noreply@tenderwatch.dev',
    ],

    'api' => [
        'rate_limit_requests' => 60,
        'rate_limit_window' => 60,
    ],
];
