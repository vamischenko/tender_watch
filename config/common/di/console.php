<?php

declare(strict_types=1);

use App\Console\IngestCommand;
use App\Console\MigrateCommand;
use App\Console\NotificationWorkerCommand;
use App\Ingestion\Infrastructure\FakeTenderConnector;
use App\Ingestion\Infrastructure\ZakupkiGovConnector;
use App\Notifications\Infrastructure\Channel\EmailChannel;
use App\Notifications\Infrastructure\Channel\TelegramChannel;
use App\Shared\Infrastructure\Migration\M001CreateSchema;

return [
    MigrateCommand::class => static function (\Psr\Container\ContainerInterface $c): MigrateCommand {
        return new MigrateCommand($c->get(M001CreateSchema::class));
    },

    IngestCommand::class => static function (\Psr\Container\ContainerInterface $c): IngestCommand {
        return new IngestCommand(
            useCase: $c->get(\App\Ingestion\Application\IngestTendersUseCase::class),
            fakeConnector: $c->get(FakeTenderConnector::class),
            zakupkiConnector: $c->get(ZakupkiGovConnector::class),
        );
    },

    NotificationWorkerCommand::class => static function (\Psr\Container\ContainerInterface $c): NotificationWorkerCommand {
        return new NotificationWorkerCommand(
            consumer: $c->get(\App\Shared\Infrastructure\Queue\RabbitMQConsumer::class),
            userRepository: $c->get(\App\Identity\Domain\Repository\UserRepositoryInterface::class),
            channels: [
                $c->get(EmailChannel::class),
                $c->get(TelegramChannel::class),
            ],
        );
    },
];
