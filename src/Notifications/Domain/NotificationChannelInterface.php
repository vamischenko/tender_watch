<?php

declare(strict_types=1);

namespace App\Notifications\Domain;

interface NotificationChannelInterface
{
    public function supports(string $channelType): bool;

    public function send(NotificationMessage $message): void;
}
