<?php

declare(strict_types=1);

namespace App\Ingestion\Domain;

final class RawTenderDTO
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $title,
        public readonly string $description,
        public readonly string $categoryName,
        public readonly int $budgetAmount,
        public readonly string $budgetCurrency,
        public readonly string $region,
        public readonly \DateTimeImmutable $publishedAt,
        public readonly \DateTimeImmutable $deadlineAt,
        public readonly string $sourceType,
        /** @var array<string, mixed> */
        public readonly array $raw = [],
    ) {
    }
}
