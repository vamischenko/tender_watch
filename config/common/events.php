<?php

declare(strict_types=1);

use App\Tenders\Domain\Event\TenderCreated;
use App\Tenders\Domain\Event\TenderUpdated;
use App\Matching\Application\Listener\RunMatchingOnTenderCreated;
use App\Matching\Application\Listener\InvalidateCacheOnTenderUpdated;
use App\Subscriptions\Domain\Event\SubscriptionMatched;
use App\Notifications\Application\Listener\EnqueueNotificationOnMatch;

return [
    TenderCreated::class => [
        RunMatchingOnTenderCreated::class,
    ],
    TenderUpdated::class => [
        InvalidateCacheOnTenderUpdated::class,
    ],
    SubscriptionMatched::class => [
        EnqueueNotificationOnMatch::class,
    ],
];
