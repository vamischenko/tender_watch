<?php

declare(strict_types=1);

use App\Tenders\Domain\Repository\TenderRepositoryInterface;
use App\Tenders\Domain\Repository\CategoryRepositoryInterface;
use App\Tenders\Infrastructure\Persistence\DbTenderRepository;
use App\Tenders\Infrastructure\Persistence\DbCategoryRepository;

return [
    TenderRepositoryInterface::class => DbTenderRepository::class,
    CategoryRepositoryInterface::class => DbCategoryRepository::class,
];
