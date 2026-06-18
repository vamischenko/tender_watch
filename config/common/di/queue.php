<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Queue\RabbitMQConsumer;
use App\Shared\Infrastructure\Queue\RabbitMQPublisher;

return [
    RabbitMQPublisher::class => static function (\Psr\Container\ContainerInterface $c): RabbitMQPublisher {
        return new RabbitMQPublisher(
            host: getenv('RABBITMQ_HOST') ?: 'rabbitmq',
            port: (int)(getenv('RABBITMQ_PORT') ?: 5672),
            user: getenv('RABBITMQ_USER') ?: 'guest',
            password: getenv('RABBITMQ_PASSWORD') ?: 'guest',
        );
    },

    RabbitMQConsumer::class => static function (\Psr\Container\ContainerInterface $c): RabbitMQConsumer {
        return new RabbitMQConsumer(
            host: getenv('RABBITMQ_HOST') ?: 'rabbitmq',
            port: (int)(getenv('RABBITMQ_PORT') ?: 5672),
            user: getenv('RABBITMQ_USER') ?: 'guest',
            password: getenv('RABBITMQ_PASSWORD') ?: 'guest',
            logger: $c->get(\Psr\Log\LoggerInterface::class),
        );
    },
];
