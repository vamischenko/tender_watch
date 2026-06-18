<?php

declare(strict_types=1);

namespace App\Tests\Unit\Subscriptions;

use App\Subscriptions\Domain\ValueObject\FilterCriteria;
use PHPUnit\Framework\TestCase;

final class FilterCriteriaTest extends TestCase
{
    public function testCreatesFromEmptyArray(): void
    {
        $criteria = FilterCriteria::fromArray([]);

        $this->assertEmpty($criteria->getCategoryIds());
        $this->assertEmpty($criteria->getRegions());
        $this->assertEmpty($criteria->getKeywords());
        $this->assertNull($criteria->getMinBudget());
        $this->assertNull($criteria->getMaxBudget());
    }

    public function testCreatesFromFullArray(): void
    {
        $criteria = FilterCriteria::fromArray([
            'category_ids' => ['cat1', 'cat2'],
            'min_budget' => 50000,
            'max_budget' => 1000000,
            'regions' => ['Moscow'],
            'keywords' => ['строительство'],
        ]);

        $this->assertSame(['cat1', 'cat2'], $criteria->getCategoryIds());
        $this->assertSame(50000, $criteria->getMinBudget());
        $this->assertSame(1000000, $criteria->getMaxBudget());
        $this->assertSame(['Moscow'], $criteria->getRegions());
        $this->assertSame(['строительство'], $criteria->getKeywords());
    }

    public function testToArrayRoundTrip(): void
    {
        $data = [
            'category_ids' => ['cat1'],
            'min_budget' => 100,
            'max_budget' => 500,
            'regions' => ['SPb'],
            'keywords' => ['ремонт'],
        ];

        $criteria = FilterCriteria::fromArray($data);
        $this->assertSame($data, $criteria->toArray());
    }
}
