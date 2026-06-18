<?php

declare(strict_types=1);

namespace App\Tenders\Application\Query;

use App\Tenders\Domain\Repository\TenderCollection;
use App\Tenders\Domain\Repository\TenderFilter;
use App\Tenders\Domain\Repository\TenderRepositoryInterface;

final class GetTendersQuery
{
    public function __construct(private readonly TenderRepositoryInterface $repository)
    {
    }

    public function execute(
        ?string $categoryId = null,
        ?string $region = null,
        ?int $minBudget = null,
        ?int $maxBudget = null,
        ?string $query = null,
        int $page = 1,
        int $perPage = 20,
    ): TenderCollection {
        $filter = new TenderFilter(
            categoryId: $categoryId,
            region: $region,
            minBudget: $minBudget,
            maxBudget: $maxBudget,
            query: $query,
        );

        return $this->repository->findAll($filter, $page, max(1, min(100, $perPage)));
    }
}
