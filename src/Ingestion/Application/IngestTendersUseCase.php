<?php

declare(strict_types=1);

namespace App\Ingestion\Application;

use App\Ingestion\Domain\NormalizerInterface;
use App\Ingestion\Domain\SourceConnectorInterface;
use App\Tenders\Domain\Repository\CategoryRepositoryInterface;
use App\Tenders\Domain\Repository\TenderRepositoryInterface;
use Psr\Log\LoggerInterface;

final class IngestTendersUseCase
{
    public function __construct(
        private readonly NormalizerInterface $normalizer,
        private readonly TenderRepositoryInterface $tenderRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function execute(SourceConnectorInterface $connector, int $pageSize = 50): IngestResult
    {
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $categories = $this->categoryRepository->findAll();
        $defaultCategoryId = empty($categories) ? 'default' : $categories[0]->getId();

        $totalPages = $connector->getTotalPages($pageSize);

        for ($page = 1; $page <= $totalPages; $page++) {
            $rawItems = $connector->fetch($page, $pageSize);

            foreach ($rawItems as $dto) {
                $sourceId = $connector->getType() . ':' . $dto->externalId;

                if ($this->tenderRepository->existsBySourceId($sourceId)) {
                    $skipped++;
                    continue;
                }

                try {
                    $categoryId = $this->resolveCategoryId($dto->categoryName, $categories, $defaultCategoryId);
                    $tender = $this->normalizer->normalize($dto, $categoryId);
                    $this->tenderRepository->save($tender);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->logger->error('Failed to import tender', [
                        'external_id' => $dto->externalId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->info("Ingested page {$page}/{$totalPages}", [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        }

        return new IngestResult($imported, $skipped, $errors);
    }

    /**
     * @param \App\Tenders\Domain\Entity\Category[] $categories
     */
    private function resolveCategoryId(string $categoryName, array $categories, string $default): string
    {
        $normalized = mb_strtolower(trim($categoryName));
        foreach ($categories as $category) {
            if (
                mb_strtolower($category->getName()) === $normalized
                || mb_strtolower($category->getSlug()) === $normalized
            ) {
                return $category->getId();
            }
        }
        return $default;
    }
}
