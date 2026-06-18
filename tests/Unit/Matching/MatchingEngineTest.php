<?php

declare(strict_types=1);

namespace App\Tests\Unit\Matching;

use App\Matching\Domain\MatchingEngine;
use App\Subscriptions\Domain\Entity\Subscription;
use App\Subscriptions\Domain\ValueObject\FilterCriteria;
use App\Tenders\Domain\Entity\Tender;
use App\Tenders\Domain\Entity\TenderId;
use App\Tenders\Domain\ValueObject\DateRange;
use App\Tenders\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MatchingEngineTest extends TestCase
{
    private MatchingEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new MatchingEngine();
    }

    public function testMatchesWhenAllCriteriaEmpty(): void
    {
        $tender = $this->makeTender(categoryId: 'cat1', region: 'Moscow', amount: 50000);
        $subscription = $this->makeSubscription(criteria: []);

        $results = $this->engine->match($tender, [$subscription]);

        $this->assertCount(1, $results);
    }

    public function testMatchesByCategory(): void
    {
        $tender = $this->makeTender(categoryId: 'cat1');
        $match = $this->makeSubscription(criteria: ['category_ids' => ['cat1']]);
        $noMatch = $this->makeSubscription(criteria: ['category_ids' => ['cat2']]);

        $this->assertCount(1, $this->engine->match($tender, [$match, $noMatch]));
    }

    public function testMatchesByBudgetRange(): void
    {
        $tender = $this->makeTender(amount: 100000);
        $match = $this->makeSubscription(criteria: ['min_budget' => 50000, 'max_budget' => 200000]);
        $noMatchLow = $this->makeSubscription(criteria: ['min_budget' => 150000]);
        $noMatchHigh = $this->makeSubscription(criteria: ['max_budget' => 50000]);

        $results = $this->engine->match($tender, [$match, $noMatchLow, $noMatchHigh]);

        $this->assertCount(1, $results);
    }

    public function testMatchesByRegion(): void
    {
        $tender = $this->makeTender(region: 'Moscow');
        $match = $this->makeSubscription(criteria: ['regions' => ['Moscow', 'SPb']]);
        $noMatch = $this->makeSubscription(criteria: ['regions' => ['Kazan']]);

        $this->assertCount(1, $this->engine->match($tender, [$match, $noMatch]));
    }

    public function testMatchesByKeyword(): void
    {
        $tender = $this->makeTender(title: 'Ремонт дорог в Москве');
        $match = $this->makeSubscription(criteria: ['keywords' => ['дорог', 'мост']]);
        $noMatch = $this->makeSubscription(criteria: ['keywords' => ['самолёт']]);

        $this->assertCount(1, $this->engine->match($tender, [$match, $noMatch]));
    }

    public function testSkipsInactiveSubscriptions(): void
    {
        $tender = $this->makeTender();
        $inactive = $this->makeSubscription(criteria: [], isActive: false);

        $this->assertCount(0, $this->engine->match($tender, [$inactive]));
    }

    private function makeTender(
        string $categoryId = 'cat1',
        string $region = 'Moscow',
        int $amount = 100000,
        string $title = 'Test Tender',
    ): Tender {
        return Tender::create(
            id: TenderId::generate(),
            title: $title,
            description: 'Test description',
            categoryId: $categoryId,
            budget: new Money($amount, 'RUB'),
            region: $region,
            deadline: new DateRange(
                new \DateTimeImmutable('-1 day'),
                new \DateTimeImmutable('+30 days'),
            ),
            sourceId: 'src-' . uniqid(),
        );
    }

    private function makeSubscription(array $criteria, bool $isActive = true): Subscription
    {
        return Subscription::restore(
            id: \Ramsey\Uuid\Uuid::uuid7()->toString(),
            userId: 'user-1',
            name: 'Test',
            criteria: FilterCriteria::fromArray($criteria),
            channels: ['email'],
            isActive: $isActive,
            createdAt: new \DateTimeImmutable(),
        );
    }
}
