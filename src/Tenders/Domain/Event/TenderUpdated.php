<?php

declare(strict_types=1);

namespace App\Tenders\Domain\Event;

final class TenderUpdated
{
    public function __construct(
        public readonly string $tenderId,
        /** @var string[] */
        public readonly array $changedFields,
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}
