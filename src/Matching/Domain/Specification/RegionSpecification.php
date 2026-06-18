<?php

declare(strict_types=1);

namespace App\Matching\Domain\Specification;

use App\Tenders\Domain\Entity\Tender;

final class RegionSpecification extends AbstractSpecification
{
    /** @param string[] $allowedRegions */
    public function __construct(private readonly array $allowedRegions)
    {
    }

    public function isSatisfiedBy(Tender $tender): bool
    {
        if (empty($this->allowedRegions)) {
            return true;
        }
        return in_array($tender->getRegion(), $this->allowedRegions, true);
    }
}
