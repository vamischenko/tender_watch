<?php

declare(strict_types=1);

namespace App\Matching\Domain\Specification;

use App\Tenders\Domain\Entity\Tender;

final class KeywordSpecification extends AbstractSpecification
{
    /** @param string[] $keywords */
    public function __construct(private readonly array $keywords)
    {
    }

    public function isSatisfiedBy(Tender $tender): bool
    {
        if (empty($this->keywords)) {
            return true;
        }

        $haystack = mb_strtolower($tender->getTitle() . ' ' . $tender->getDescription());

        foreach ($this->keywords as $keyword) {
            if (str_contains($haystack, mb_strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }
}
