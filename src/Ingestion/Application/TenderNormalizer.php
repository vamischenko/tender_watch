<?php

declare(strict_types=1);

namespace App\Ingestion\Application;

use App\Ingestion\Domain\NormalizerInterface;
use App\Ingestion\Domain\RawTenderDTO;
use App\Tenders\Domain\Entity\Tender;
use App\Tenders\Domain\Entity\TenderId;
use App\Tenders\Domain\ValueObject\DateRange;
use App\Tenders\Domain\ValueObject\Money;

final class TenderNormalizer implements NormalizerInterface
{
    public function normalize(RawTenderDTO $dto, string $categoryId): Tender
    {
        return Tender::create(
            id: TenderId::generate(),
            title: mb_substr(trim($dto->title), 0, 500),
            description: trim($dto->description),
            categoryId: $categoryId,
            budget: new Money(max(0, $dto->budgetAmount), $dto->budgetCurrency ?: 'RUB'),
            region: $dto->region ?: 'Не указан',
            deadline: new DateRange($dto->publishedAt, $dto->deadlineAt),
            sourceId: $dto->sourceType . ':' . $dto->externalId,
        );
    }
}
