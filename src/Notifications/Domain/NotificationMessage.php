<?php

declare(strict_types=1);

namespace App\Notifications\Domain;

final class NotificationMessage
{
    public function __construct(
        public readonly string $userId,
        public readonly string $channelType,
        public readonly string $target,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $tenderId,
        public readonly string $subscriptionId,
    ) {
    }
}
