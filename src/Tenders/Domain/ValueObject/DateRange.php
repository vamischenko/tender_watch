<?php

declare(strict_types=1);

namespace App\Tenders\Domain\ValueObject;

final class DateRange
{
    public function __construct(
        private readonly \DateTimeImmutable $publishedAt,
        private readonly \DateTimeImmutable $deadlineAt,
    ) {
        if ($deadlineAt <= $publishedAt) {
            throw new \InvalidArgumentException('Deadline must be after publish date');
        }
    }

    public function getPublishedAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getDeadlineAt(): \DateTimeImmutable
    {
        return $this->deadlineAt;
    }

    public function isExpired(\DateTimeImmutable $now = null): bool
    {
        return ($now ?? new \DateTimeImmutable()) > $this->deadlineAt;
    }

    public function daysUntilDeadline(\DateTimeImmutable $now = null): int
    {
        $diff = ($now ?? new \DateTimeImmutable())->diff($this->deadlineAt);
        return $diff->invert ? 0 : (int)$diff->days;
    }
}
