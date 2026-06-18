<?php

declare(strict_types=1);

namespace App\Subscriptions\Infrastructure\Persistence;

use App\Subscriptions\Domain\Entity\Subscription;
use App\Subscriptions\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscriptions\Domain\ValueObject\FilterCriteria;
use Yiisoft\Db\Connection\ConnectionInterface;

final class DbSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function findById(string $id): ?Subscription
    {
        $row = $this->db->createCommand(
            'SELECT s.*, array_agg(sc.channel_type) as channels FROM subscriptions s
             LEFT JOIN subscription_channels sc ON sc.subscription_id = s.id
             WHERE s.id = :id GROUP BY s.id LIMIT 1'
        )->bindValue(':id', $id)->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findActiveAll(): array
    {
        $rows = $this->db->createCommand(
            'SELECT s.*, array_agg(sc.channel_type) as channels FROM subscriptions s
             LEFT JOIN subscription_channels sc ON sc.subscription_id = s.id
             WHERE s.is_active = TRUE GROUP BY s.id'
        )->queryAll();

        return array_map($this->hydrate(...), $rows);
    }

    public function findByUserId(string $userId): array
    {
        $rows = $this->db->createCommand(
            'SELECT s.*, array_agg(sc.channel_type) as channels FROM subscriptions s
             LEFT JOIN subscription_channels sc ON sc.subscription_id = s.id
             WHERE s.user_id = :user_id GROUP BY s.id ORDER BY s.created_at DESC',
            [':user_id' => $userId]
        )->queryAll();

        return array_map($this->hydrate(...), $rows);
    }

    public function save(Subscription $subscription): void
    {
        $this->db->createCommand()->upsert('subscriptions', [
            'id' => $subscription->getId(),
            'user_id' => $subscription->getUserId(),
            'name' => $subscription->getName(),
            'criteria_json' => json_encode($subscription->getCriteria()->toArray(), JSON_THROW_ON_ERROR),
            'is_active' => $subscription->isActive(),
            'created_at' => $subscription->getCreatedAt()->format('Y-m-d H:i:s'),
        ], true)->execute();

        $this->db->createCommand()
            ->delete('subscription_channels', ['subscription_id' => $subscription->getId()])
            ->execute();

        foreach ($subscription->getChannels() as $channelType) {
            $this->db->createCommand()->insert('subscription_channels', [
                'subscription_id' => $subscription->getId(),
                'channel_type' => $channelType,
                'target' => '',
            ])->execute();
        }
    }

    public function delete(string $id): void
    {
        $this->db->createCommand()->delete('subscriptions', ['id' => $id])->execute();
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Subscription
    {
        $criteria = FilterCriteria::fromArray(
            json_decode($row['criteria_json'], true, 512, JSON_THROW_ON_ERROR)
        );

        $channels = $row['channels'] ? array_filter(explode(',', trim($row['channels'], '{}'))) : [];

        return Subscription::restore(
            id: $row['id'],
            userId: $row['user_id'],
            name: $row['name'],
            criteria: $criteria,
            channels: $channels,
            isActive: (bool)$row['is_active'],
            createdAt: new \DateTimeImmutable($row['created_at']),
        );
    }
}
