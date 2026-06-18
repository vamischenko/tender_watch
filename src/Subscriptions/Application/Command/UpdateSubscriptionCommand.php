<?php

declare(strict_types=1);

namespace App\Subscriptions\Application\Command;

use App\Subscriptions\Domain\Entity\Subscription;
use App\Subscriptions\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscriptions\Domain\ValueObject\FilterCriteria;

final class UpdateSubscriptionCommand
{
    public function __construct(private readonly SubscriptionRepositoryInterface $repository)
    {
    }

    /**
     * @param array<string, mixed>|null $criteria
     * @param string[]|null $channels
     */
    public function execute(
        string $id,
        string $currentUserId,
        ?array $criteria = null,
        ?array $channels = null,
    ): Subscription {
        $subscription = $this->repository->findById($id);

        if ($subscription === null) {
            throw new \DomainException("Subscription {$id} not found");
        }
        if ($subscription->getUserId() !== $currentUserId) {
            throw new \DomainException('Access denied');
        }

        if ($criteria !== null) {
            $subscription->updateCriteria(FilterCriteria::fromArray($criteria));
        }
        if ($channels !== null) {
            $subscription->updateChannels($channels);
        }

        $this->repository->save($subscription);

        return $subscription;
    }
}
