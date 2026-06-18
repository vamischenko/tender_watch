<?php

declare(strict_types=1);

namespace App\Tenders\Domain\Entity;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class TenderId
{
    private function __construct(private readonly UuidInterface $value)
    {
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid7());
    }

    public static function fromString(string $value): self
    {
        return new self(Uuid::fromString($value));
    }

    public function toString(): string
    {
        return $this->value->toString();
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
