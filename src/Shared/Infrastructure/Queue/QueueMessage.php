<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

final class QueueMessage
{
    public function __construct(
        public readonly string $type,
        /** @var array<string, mixed> */
        public readonly array $payload,
        public readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
    }

    public function toJson(): string
    {
        return json_encode([
            'type' => $this->type,
            'payload' => $this->payload,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR);
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return new self(
            type: $data['type'],
            payload: $data['payload'],
            createdAt: new \DateTimeImmutable($data['created_at']),
        );
    }
}
