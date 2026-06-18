<?php

declare(strict_types=1);

namespace App\Tests\Unit\Tenders;

use App\Tenders\Domain\ValueObject\DateRange;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function testIsNotExpiredForFutureDeadline(): void
    {
        $range = new DateRange(
            new \DateTimeImmutable('-1 day'),
            new \DateTimeImmutable('+30 days'),
        );

        $this->assertFalse($range->isExpired());
    }

    public function testIsExpiredForPastDeadline(): void
    {
        $range = new DateRange(
            new \DateTimeImmutable('-10 days'),
            new \DateTimeImmutable('-1 day'),
        );

        $this->assertTrue($range->isExpired());
    }

    public function testRejectsDeadlineBeforePublish(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DateRange(
            new \DateTimeImmutable('+10 days'),
            new \DateTimeImmutable('+5 days'),
        );
    }

    public function testDaysUntilDeadline(): void
    {
        $range = new DateRange(
            new \DateTimeImmutable('-1 day'),
            new \DateTimeImmutable('+10 days'),
        );

        $this->assertGreaterThanOrEqual(9, $range->daysUntilDeadline());
    }
}
