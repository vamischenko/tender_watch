<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entity;

use Ramsey\Uuid\Uuid;

final class User
{
    private function __construct(
        private readonly string $id,
        private string $email,
        private string $passwordHash,
        private ?string $telegramChatId,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(string $email, string $passwordHash): self
    {
        return new self(
            id: Uuid::uuid7()->toString(),
            email: $email,
            passwordHash: $passwordHash,
            telegramChatId: null,
            createdAt: new \DateTimeImmutable(),
        );
    }

    public static function restore(
        string $id,
        string $email,
        string $passwordHash,
        ?string $telegramChatId,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $email, $passwordHash, $telegramChatId, $createdAt);
    }

    public function linkTelegram(string $chatId): void
    {
        $this->telegramChatId = $chatId;
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getEmail(): string
    {
        return $this->email;
    }
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }
    public function getTelegramChatId(): ?string
    {
        return $this->telegramChatId;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
