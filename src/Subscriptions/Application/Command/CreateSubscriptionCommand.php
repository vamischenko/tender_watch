<?php

declare(strict_types=1);

namespace App\Subscriptions\Application\Command;

use App\Subscriptions\Domain\Entity\Subscription;
use App\Subscriptions\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscriptions\Domain\ValueObject\FilterCriteria;

final class CreateSubscriptionCommand
{
    public function __construct(private readonly SubscriptionRepositoryInterface $repository)
    {
    }

    /**
     * @param array<string, mixed> $criteria
     * @param string[] $channels
     */
    public function execute(
        string $userId,
        string $name,
        array $criteria,
        array $channels,
    ): Subscription {
        $subscription = Subscription::create(
            userId: $userId,
            name: $name,
            criteria: FilterCriteria::fromArray($criteria),
            channels: $channels,
        );

        $this->repository->save($subscription);

        return $subscription;
    }
}
