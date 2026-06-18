<?php

declare(strict_types=1);

namespace App\Subscriptions\Domain\Entity;

use App\Subscriptions\Domain\ValueObject\FilterCriteria;
use Ramsey\Uuid\Uuid;

final class Subscription
{
    private function __construct(
        private readonly string $id,
        private readonly string $userId,
        private string $name,
        private FilterCriteria $criteria,
        /** @var string[] */
        private array $channels,
        private bool $isActive,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * @param string[] $channels
     */
    public static function create(
        string $userId,
        string $name,
        FilterCriteria $criteria,
        array $channels,
    ): self {
        return new self(
            id: Uuid::uuid7()->toString(),
            userId: $userId,
            name: $name,
            criteria: $criteria,
            channels: $channels,
            isActive: true,
            createdAt: new \DateTimeImmutable(),
        );
    }

    /**
     * @param string[] $channels
     */
    public static function restore(
        string $id,
        string $userId,
        string $name,
        FilterCriteria $criteria,
        array $channels,
        bool $isActive,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $userId, $name, $criteria, $channels, $isActive, $createdAt);
    }

    public function updateCriteria(FilterCriteria $criteria): void
    {
        $this->criteria = $criteria;
    }

    /** @param string[] $channels */
    public function updateChannels(array $channels): void
    {
        $this->channels = $channels;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getUserId(): string
    {
        return $this->userId;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getCriteria(): FilterCriteria
    {
        return $this->criteria;
    }
    /** @return string[] */
    public function getChannels(): array
    {
        return $this->channels;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
