<?php

declare(strict_types=1);

namespace App\Subscriptions\Domain\Repository;

use App\Subscriptions\Domain\Entity\Subscription;

interface SubscriptionRepositoryInterface
{
    public function findById(string $id): ?Subscription;

    /** @return Subscription[] */
    public function findActiveAll(): array;

    /** @return Subscription[] */
    public function findByUserId(string $userId): array;

    public function save(Subscription $subscription): void;

    public function delete(string $id): void;
}
