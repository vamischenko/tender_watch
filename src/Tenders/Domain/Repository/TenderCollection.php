<?php

declare(strict_types=1);

namespace App\Tenders\Domain\Repository;

use App\Tenders\Domain\Entity\Tender;

final class TenderCollection
{
    /** @param Tender[] $items */
    public function __construct(
        private readonly array $items,
        private readonly int $total,
        private readonly int $page,
        private readonly int $perPage,
    ) {
    }

    /** @return Tender[] */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalPages(): int
    {
        return (int)ceil($this->total / $this->perPage);
    }
}
