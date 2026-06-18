<?php

declare(strict_types=1);

namespace App\Tenders\Domain\Event;

final class TenderCreated
{
    public function __construct(
        public readonly string $tenderId,
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}
