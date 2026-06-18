<?php

declare(strict_types=1);

namespace App\Ingestion\Application;

final class IngestResult
{
    public function __construct(
        public readonly int $imported,
        public readonly int $skipped,
        public readonly int $errors,
    ) {
    }

    public function total(): int
    {
        return $this->imported + $this->skipped + $this->errors;
    }
}
