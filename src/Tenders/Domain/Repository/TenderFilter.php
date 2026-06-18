<?php

declare(strict_types=1);

namespace App\Tenders\Domain\Repository;

final class TenderFilter
{
    public function __construct(
        public readonly ?string $categoryId = null,
        public readonly ?string $region = null,
        public readonly ?int $minBudget = null,
        public readonly ?int $maxBudget = null,
        public readonly ?string $query = null,
        public readonly bool $activeOnly = true,
    ) {
    }
}
