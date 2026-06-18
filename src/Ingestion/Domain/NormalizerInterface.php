<?php

declare(strict_types=1);

namespace App\Ingestion\Domain;

use App\Tenders\Domain\Entity\Tender;

interface NormalizerInterface
{
    public function normalize(RawTenderDTO $dto, string $categoryId): Tender;
}
