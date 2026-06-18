<?php

declare(strict_types=1);

namespace App\Tenders\Presentation\Controller;

use App\Tenders\Application\Query\GetTendersQuery;
use App\Tenders\Domain\Repository\TenderRepositoryInterface;
use App\Tenders\Domain\Entity\TenderId;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TenderController
{
    public function __construct(
        private readonly GetTendersQuery $getTendersQuery,
        private readonly TenderRepositoryInterface $tenderRepository,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    #[OA\Get(
        path: '/tenders',
        summary: 'Список тендеров',
        security: [['ApiKey' => []]],
        tags: ['Tenders'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: false,
                description: 'Полнотекстовый поиск',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'region',
                in: 'query',
                required: false,
                description: 'Регион',
                schema: new OA\Schema(type: 'string', example: 'Москва')
            ),
            new OA\Parameter(
                name: 'category',
                in: 'query',
                required: false,
                description: 'UUID категории',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'min_budget',
                in: 'query',
                required: false,
                description: 'Минимальный бюджет (в копейках)',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'max_budget',
                in: 'query',
                required: false,
                description: 'Максимальный бюджет (в копейках)',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Страница',
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Элементов на странице',
                schema: new OA\Schema(type: 'integer', default: 20)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список тендеров',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Tender'),
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(property: 'page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'total_pages', type: 'integer'),
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
            new OA\Response(response: 429, description: 'Превышен лимит запросов'),
        ],
    )]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        $collection = $this->getTendersQuery->execute(
            categoryId: $params['category'] ?? null,
            region: $params['region'] ?? null,
            minBudget: isset($params['min_budget']) ? (int)$params['min_budget'] : null,
            maxBudget: isset($params['max_budget']) ? (int)$params['max_budget'] : null,
            query: $params['q'] ?? null,
            page: (int)($params['page'] ?? 1),
            perPage: (int)($params['per_page'] ?? 20),
        );

        return $this->json([
            'success' => true,
            'data' => array_map($this->serializeTender(...), $collection->getItems()),
            'meta' => [
                'total' => $collection->getTotal(),
                'page' => $collection->getPage(),
                'per_page' => $collection->getPerPage(),
                'total_pages' => $collection->getTotalPages(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/tenders/{id}',
        summary: 'Тендер по ID',
        security: [['ApiKey' => []]],
        tags: ['Tenders'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'UUID тендера',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Тендер найден',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Tender'),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: 'Неверный формат UUID',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Тендер не найден',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ],
    )]
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        try {
            $tender = $this->tenderRepository->findById(TenderId::fromString($id));
        } catch (\Throwable) {
            return $this->json(['success' => false, 'message' => 'Invalid ID format'], 400);
        }

        if ($tender === null) {
            return $this->json(['success' => false, 'message' => 'Tender not found'], 404);
        }

        return $this->json(['success' => true, 'data' => $this->serializeTender($tender)]);
    }

    /** @return array<string, mixed> */
    private function serializeTender(\App\Tenders\Domain\Entity\Tender $tender): array
    {
        return [
            'id' => $tender->getId()->toString(),
            'title' => $tender->getTitle(),
            'description' => $tender->getDescription(),
            'category_id' => $tender->getCategoryId(),
            'budget' => [
                'amount' => $tender->getBudget()->getAmount(),
                'currency' => $tender->getBudget()->getCurrency(),
            ],
            'region' => $tender->getRegion(),
            'published_at' => $tender->getDeadline()->getPublishedAt()->format(\DateTimeInterface::ATOM),
            'deadline_at' => $tender->getDeadline()->getDeadlineAt()->format(\DateTimeInterface::ATOM),
            'is_expired' => $tender->getDeadline()->isExpired(),
            'status' => $tender->getStatus()->value,
            'created_at' => $tender->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
