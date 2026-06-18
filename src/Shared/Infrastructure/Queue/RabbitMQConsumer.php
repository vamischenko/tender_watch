<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

final class RabbitMQConsumer
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly LoggerInterface $logger,
        private readonly string $exchange = 'tenderwatch',
    ) {
    }

    /**
     * @param callable(QueueMessage): bool $handler  возвращает true = ack, false = nack+requeue
     */
    public function consume(string $queue, callable $handler): void
    {
        $connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
        $channel = $connection->channel();

        $channel->queue_declare($queue, false, true, false, false);
        $channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $channel->queue_bind($queue, $this->exchange, $queue);
        $channel->basic_qos(0, 1, false);

        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $amqpMsg) use ($handler, $channel): void {
                try {
                    $message = QueueMessage::fromJson($amqpMsg->getBody());
                    $success = $handler($message);

                    if ($success) {
                        $channel->basic_ack($amqpMsg->getDeliveryTag());
                    } else {
                        $channel->basic_nack($amqpMsg->getDeliveryTag(), false, true);
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Queue handler error', ['error' => $e->getMessage()]);
                    $channel->basic_nack($amqpMsg->getDeliveryTag(), false, false);
                }
            }
        );

        $this->logger->info("Waiting for messages on queue: {$queue}");

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
