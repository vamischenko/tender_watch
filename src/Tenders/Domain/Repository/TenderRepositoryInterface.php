<?php

declare(strict_types=1);

namespace App\Tenders\Domain\Repository;

use App\Tenders\Domain\Entity\Tender;
use App\Tenders\Domain\Entity\TenderId;

interface TenderRepositoryInterface
{
    public function findById(TenderId $id): ?Tender;

    public function findAll(TenderFilter $filter, int $page, int $perPage): TenderCollection;

    public function save(Tender $tender): void;

    public function existsBySourceId(string $sourceId): bool;
}
