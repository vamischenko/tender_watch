<?php

declare(strict_types=1);

namespace App\Matching\Domain\Specification;

use App\Tenders\Domain\Entity\Tender;

final class OrSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly SpecificationInterface $left,
        private readonly SpecificationInterface $right,
    ) {
    }

    public function isSatisfiedBy(Tender $tender): bool
    {
        return $this->left->isSatisfiedBy($tender) || $this->right->isSatisfiedBy($tender);
    }
}
