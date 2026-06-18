<?php

declare(strict_types=1);

namespace App\Matching\Domain\Specification;

use App\Tenders\Domain\Entity\Tender;

final class BudgetSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly ?int $minBudget,
        private readonly ?int $maxBudget,
    ) {
    }

    public function isSatisfiedBy(Tender $tender): bool
    {
        $amount = $tender->getBudget()->getAmount();

        if ($this->minBudget !== null && $amount < $this->minBudget) {
            return false;
        }
        if ($this->maxBudget !== null && $amount > $this->maxBudget) {
            return false;
        }
        return true;
    }
}
