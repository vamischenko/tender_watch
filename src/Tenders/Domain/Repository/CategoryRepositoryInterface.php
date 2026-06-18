<?php

declare(strict_types=1);

namespace App\Tenders\Domain\Repository;

use App\Tenders\Domain\Entity\Category;

interface CategoryRepositoryInterface
{
    public function findById(string $id): ?Category;

    /** @return Category[] */
    public function findAll(): array;

    public function save(Category $category): void;
}
