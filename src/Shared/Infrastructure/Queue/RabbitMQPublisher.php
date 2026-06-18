<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMQPublisher
{
    private ?AMQPStreamConnection $connection = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $exchange = 'tenderwatch',
    ) {
    }

    public function publish(string $queue, QueueMessage $message): void
    {
        $connection = $this->getConnection();
        $channel = $connection->channel();

        $channel->queue_declare($queue, false, true, false, false);
        $channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $channel->queue_bind($queue, $this->exchange, $queue);

        $amqpMessage = new AMQPMessage(
            $message->toJson(),
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json',
            ]
        );

        $channel->basic_publish($amqpMessage, $this->exchange, $queue);
        $channel->close();
    }

    private function getConnection(): AMQPStreamConnection
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password
            );
        }
        return $this->connection;
    }

    public function __destruct()
    {
        $this->connection?->close();
    }
}
