<?php

declare(strict_types=1);

use App\Console;

return [
    'hello'                => Console\HelloCommand::class,
    'migrate'              => Console\MigrateCommand::class,
    'tender:ingest'        => Console\IngestCommand::class,
    'worker:notifications' => Console\NotificationWorkerCommand::class,
];
