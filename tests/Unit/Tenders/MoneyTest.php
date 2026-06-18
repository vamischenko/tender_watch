<?php

declare(strict_types=1);

namespace App\Tests\Unit\Tenders;

use App\Tenders\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testCreatesMoney(): void
    {
        $money = new Money(10000, 'RUB');
        $this->assertSame(10000, $money->getAmount());
        $this->assertSame('RUB', $money->getCurrency());
    }

    public function testRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(-1, 'RUB');
    }

    public function testRejectsInvalidCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(100, 'rub');
    }

    public function testEquality(): void
    {
        $a = new Money(500, 'USD');
        $b = new Money(500, 'USD');
        $c = new Money(600, 'USD');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testComparison(): void
    {
        $low = new Money(100, 'RUB');
        $high = new Money(200, 'RUB');

        $this->assertTrue($high->isGreaterThan($low));
        $this->assertTrue($low->isLessThan($high));
    }

    public function testCannotCompareDifferentCurrencies(): void
    {
        $this->expectException(\LogicException::class);
        (new Money(100, 'RUB'))->isGreaterThan(new Money(100, 'USD'));
    }
}
