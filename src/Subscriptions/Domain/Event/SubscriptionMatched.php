<?php

declare(strict_types=1);

namespace App\Subscriptions\Domain\Event;

final class SubscriptionMatched
{
    public function __construct(
        public readonly string $tenderId,
        public readonly string $subscriptionId,
        public readonly string $userId,
        /** @var string[] */
        public readonly array $channels,
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}
