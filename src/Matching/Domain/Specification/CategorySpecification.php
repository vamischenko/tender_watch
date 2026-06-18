<?php

declare(strict_types=1);

namespace App\Matching\Domain\Specification;

use App\Tenders\Domain\Entity\Tender;

final class CategorySpecification extends AbstractSpecification
{
    /** @param string[] $allowedCategoryIds */
    public function __construct(private readonly array $allowedCategoryIds)
    {
    }

    public function isSatisfiedBy(Tender $tender): bool
    {
        if (empty($this->allowedCategoryIds)) {
            return true;
        }
        return in_array($tender->getCategoryId(), $this->allowedCategoryIds, true);
    }
}
