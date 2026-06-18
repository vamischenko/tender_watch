<?php

declare(strict_types=1);

namespace App\Notifications\Application\Listener;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Infrastructure\Queue\QueueMessage;
use App\Shared\Infrastructure\Queue\RabbitMQPublisher;
use App\Subscriptions\Domain\Event\SubscriptionMatched;
use Psr\Log\LoggerInterface;

final class EnqueueNotificationOnMatch
{
    public function __construct(
        private readonly RabbitMQPublisher $publisher,
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SubscriptionMatched $event): void
    {
        $user = $this->userRepository->findById($event->userId);
        if ($user === null) {
            return;
        }

        foreach ($event->channels as $channelType) {
            try {
                $this->publisher->publish('notifications', new QueueMessage(
                    type: 'send_notification',
                    payload: [
                        'user_id' => $event->userId,
                        'channel_type' => $channelType,
                        'subject' => 'Новый тендер по вашей подписке',
                        'body' => "Найден новый тендер #{$event->tenderId}, "
                            . "подходящий под вашу подписку #{$event->subscriptionId}.",
                        'tender_id' => $event->tenderId,
                        'subscription_id' => $event->subscriptionId,
                    ],
                ));
            } catch (\Throwable $e) {
                $this->logger->error('Failed to enqueue notification', [
                    'channel' => $channelType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
