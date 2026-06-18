<?php

declare(strict_types=1);

namespace App\Matching\Domain;

final class MatchResult
{
    public function __construct(
        public readonly string $tenderId,
        public readonly string $subscriptionId,
        public readonly string $userId,
        /** @var string[] */
        public readonly array $channels,
    ) {
    }
}
