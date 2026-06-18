<?php

declare(strict_types=1);

use App\Subscriptions\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscriptions\Infrastructure\Persistence\DbSubscriptionRepository;

return [
    SubscriptionRepositoryInterface::class => DbSubscriptionRepository::class,
];
