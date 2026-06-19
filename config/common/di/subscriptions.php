<?php

declare(strict_types=1);

use App\Matching\Domain\MatchingEngine;
use App\Subscriptions\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscriptions\Infrastructure\Persistence\DbSubscriptionRepository;
use App\Subscriptions\Presentation\Controller\SubscriptionPreviewController;
use App\Tenders\Domain\Repository\TenderRepositoryInterface;

return [
    SubscriptionRepositoryInterface::class => DbSubscriptionRepository::class,

    SubscriptionPreviewController::class => static function (\Psr\Container\ContainerInterface $c): SubscriptionPreviewController {
        return new SubscriptionPreviewController(
            tenderRepository: $c->get(TenderRepositoryInterface::class),
            matchingEngine: $c->get(MatchingEngine::class),
            responseFactory: $c->get(\Psr\Http\Message\ResponseFactoryInterface::class),
        );
    },
];
