<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entity;

use Ramsey\Uuid\Uuid;

final class ApiToken
{
    private function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly string $tokenHash,
        private readonly ?\DateTimeImmutable $expiresAt,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(string $userId, string $plainToken, ?\DateTimeImmutable $expiresAt = null): self
    {
        return new self(
            id: Uuid::uuid7()->toString(),
            userId: $userId,
            tokenHash: hash('sha256', $plainToken),
            expiresAt: $expiresAt,
            createdAt: new \DateTimeImmutable(),
        );
    }

    public static function restore(
        string $id,
        string $userId,
        string $tokenHash,
        ?\DateTimeImmutable $expiresAt,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $userId, $tokenHash, $expiresAt, $createdAt);
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable();
    }

    public function matchesPlainToken(string $plainToken): bool
    {
        return hash_equals($this->tokenHash, hash('sha256', $plainToken));
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getUserId(): string
    {
        return $this->userId;
    }
    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }
    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
