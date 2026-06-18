<?php

declare(strict_types=1);

namespace App\Matching\Domain\Specification;

use App\Tenders\Domain\Entity\Tender;

interface SpecificationInterface
{
    public function isSatisfiedBy(Tender $tender): bool;

    public function and(SpecificationInterface $other): self;

    public function or(SpecificationInterface $other): self;
}
