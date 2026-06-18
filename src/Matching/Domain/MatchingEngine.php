<?php

declare(strict_types=1);

namespace App\Matching\Domain;

use App\Matching\Domain\Specification\BudgetSpecification;
use App\Matching\Domain\Specification\CategorySpecification;
use App\Matching\Domain\Specification\KeywordSpecification;
use App\Matching\Domain\Specification\RegionSpecification;
use App\Subscriptions\Domain\Entity\Subscription;
use App\Tenders\Domain\Entity\Tender;

final class MatchingEngine
{
    /**
     * @param Subscription[] $subscriptions
     * @return MatchResult[]
     */
    public function match(Tender $tender, array $subscriptions): array
    {
        $results = [];

        foreach ($subscriptions as $subscription) {
            if (!$subscription->isActive()) {
                continue;
            }

            $criteria = $subscription->getCriteria();

            $specification = (new CategorySpecification($criteria->getCategoryIds()))
                ->and(new BudgetSpecification($criteria->getMinBudget(), $criteria->getMaxBudget()))
                ->and(new RegionSpecification($criteria->getRegions()))
                ->and(new KeywordSpecification($criteria->getKeywords()));

            if ($specification->isSatisfiedBy($tender)) {
                $results[] = new MatchResult(
                    tenderId: $tender->getId()->toString(),
                    subscriptionId: $subscription->getId(),
                    userId: $subscription->getUserId(),
                    channels: $subscription->getChannels(),
                );
            }
        }

        return $results;
    }
}
