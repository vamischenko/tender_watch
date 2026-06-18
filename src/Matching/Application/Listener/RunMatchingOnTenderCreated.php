<?php

declare(strict_types=1);

namespace App\Matching\Application\Listener;

use App\Matching\Domain\MatchingEngine;
use App\Subscriptions\Domain\Event\SubscriptionMatched;
use App\Subscriptions\Domain\Repository\SubscriptionRepositoryInterface;
use App\Tenders\Domain\Event\TenderCreated;
use App\Tenders\Domain\Repository\TenderRepositoryInterface;
use App\Tenders\Domain\Entity\TenderId;
use Psr\EventDispatcher\EventDispatcherInterface;

final class RunMatchingOnTenderCreated
{
    public function __construct(
        private readonly TenderRepositoryInterface $tenderRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly MatchingEngine $matchingEngine,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(TenderCreated $event): void
    {
        $tender = $this->tenderRepository->findById(TenderId::fromString($event->tenderId));
        if ($tender === null) {
            return;
        }

        $subscriptions = $this->subscriptionRepository->findActiveAll();
        $results = $this->matchingEngine->match($tender, $subscriptions);

        foreach ($results as $result) {
            $this->eventDispatcher->dispatch(new SubscriptionMatched(
                tenderId: $result->tenderId,
                subscriptionId: $result->subscriptionId,
                userId: $result->userId,
                channels: $result->channels,
            ));
        }
    }
}
