<?php

declare(strict_types=1);

namespace App\Subscriptions\Domain\ValueObject;

final class FilterCriteria
{
    private function __construct(
        /** @var string[] */
        private readonly array $categoryIds,
        private readonly ?int $minBudget,
        private readonly ?int $maxBudget,
        /** @var string[] */
        private readonly array $regions,
        /** @var string[] */
        private readonly array $keywords,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            categoryIds: $data['category_ids'] ?? [],
            minBudget: isset($data['min_budget']) ? (int)$data['min_budget'] : null,
            maxBudget: isset($data['max_budget']) ? (int)$data['max_budget'] : null,
            regions: $data['regions'] ?? [],
            keywords: $data['keywords'] ?? [],
        );
    }

    /** @return string[] */
    public function getCategoryIds(): array
    {
        return $this->categoryIds;
    }

    public function getMinBudget(): ?int
    {
        return $this->minBudget;
    }

    public function getMaxBudget(): ?int
    {
        return $this->maxBudget;
    }

    /** @return string[] */
    public function getRegions(): array
    {
        return $this->regions;
    }

    /** @return string[] */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'category_ids' => $this->categoryIds,
            'min_budget' => $this->minBudget,
            'max_budget' => $this->maxBudget,
            'regions' => $this->regions,
            'keywords' => $this->keywords,
        ];
    }
}
