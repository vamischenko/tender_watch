<?php

declare(strict_types=1);

namespace App\Subscriptions\Presentation\Controller;

use App\Matching\Domain\MatchingEngine;
use App\Subscriptions\Domain\Entity\Subscription;
use App\Subscriptions\Domain\ValueObject\FilterCriteria;
use App\Tenders\Domain\Repository\TenderFilter;
use App\Tenders\Domain\Repository\TenderRepositoryInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SubscriptionPreviewController
{
    public function __construct(
        private readonly TenderRepositoryInterface $tenderRepository,
        private readonly MatchingEngine $matchingEngine,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    #[OA\Get(
        path: '/subscriptions/preview',
        summary: 'Предпросмотр — сколько тендеров совпадёт с критериями',
        security: [['Bearer' => []]],
        tags: ['Subscriptions'],
        parameters: [
            new OA\Parameter(
                name: 'keywords',
                in: 'query',
                required: false,
                description: 'Ключевые слова через запятую',
                schema: new OA\Schema(type: 'string', example: 'дорога,ремонт')
            ),
            new OA\Parameter(
                name: 'regions',
                in: 'query',
                required: false,
                description: 'Регионы через запятую',
                schema: new OA\Schema(type: 'string', example: 'Москва,Казань')
            ),
            new OA\Parameter(
                name: 'category_ids',
                in: 'query',
                required: false,
                description: 'UUID категорий через запятую',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'min_budget',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'max_budget',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Результат предпросмотра',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'count',
                                    type: 'integer',
                                    description: 'Количество совпадающих тендеров',
                                    example: 12
                                ),
                                new OA\Property(
                                    property: 'sample',
                                    type: 'array',
                                    description: 'Первые 5 совпадений',
                                    items: new OA\Items(ref: '#/components/schemas/Tender'),
                                ),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Не авторизован',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ],
    )]
    public function preview(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $userId = (string)$request->getAttribute('current_user_id');

        $criteria = FilterCriteria::fromArray([
            'keywords' => $this->splitParam($params['keywords'] ?? ''),
            'regions' => $this->splitParam($params['regions'] ?? ''),
            'category_ids' => $this->splitParam($params['category_ids'] ?? ''),
            'min_budget' => isset($params['min_budget']) ? (int)$params['min_budget'] : null,
            'max_budget' => isset($params['max_budget']) ? (int)$params['max_budget'] : null,
        ]);

        // Загружаем активные тендеры (первые 200 для матчинга)
        $collection = $this->tenderRepository->findAll(
            new TenderFilter(activeOnly: true),
            page: 1,
            perPage: 200,
        );

        // Создаём временную подписку только для матчинга
        $stub = Subscription::restore(
            id: 'preview',
            userId: $userId,
            name: 'preview',
            criteria: $criteria,
            channels: [],
            isActive: true,
            createdAt: new \DateTimeImmutable(),
        );

        $matched = [];
        foreach ($collection->getItems() as $tender) {
            $results = $this->matchingEngine->match($tender, [$stub]);
            if ($results !== []) {
                $matched[] = $tender;
            }
        }

        $count = count($matched);
        $sample = array_slice($matched, 0, 5);

        return $this->json([
            'success' => true,
            'data' => [
                'count' => $count,
                'sample' => array_map(fn($t) => [
                    'id' => $t->getId()->toString(),
                    'title' => $t->getTitle(),
                    'region' => $t->getRegion(),
                    'budget' => [
                        'amount' => $t->getBudget()->getAmount(),
                        'currency' => $t->getBudget()->getCurrency(),
                    ],
                    'deadline_at' => $t->getDeadline()->getDeadlineAt()->format(\DateTimeInterface::ATOM),
                    'status' => $t->getStatus()->value,
                ], $sample),
            ],
        ]);
    }

    /** @return string[] */
    private function splitParam(string $value): array
    {
        if ($value === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
