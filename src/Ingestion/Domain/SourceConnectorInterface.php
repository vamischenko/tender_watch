<?php

declare(strict_types=1);

namespace App\Ingestion\Domain;

interface SourceConnectorInterface
{
    public function getType(): string;

    /**
     * @return RawTenderDTO[]
     */
    public function fetch(int $page, int $pageSize): array;

    public function getTotalPages(int $pageSize): int;
}
