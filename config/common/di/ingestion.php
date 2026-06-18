<?php

declare(strict_types=1);

use App\Ingestion\Application\IngestTendersUseCase;
use App\Ingestion\Application\TenderNormalizer;
use App\Ingestion\Domain\NormalizerInterface;
use App\Ingestion\Infrastructure\FakeTenderConnector;
use App\Ingestion\Infrastructure\ZakupkiGovConnector;

return [
    NormalizerInterface::class => TenderNormalizer::class,

    FakeTenderConnector::class => [
        'class' => FakeTenderConnector::class,
        '__construct()' => [
            'totalItems' => 100,
        ],
    ],

    ZakupkiGovConnector::class => static function (\Psr\Container\ContainerInterface $c): ZakupkiGovConnector {
        return new ZakupkiGovConnector(
            logger: $c->get(\Psr\Log\LoggerInterface::class),
            searchQuery: '',
        );
    },
];
