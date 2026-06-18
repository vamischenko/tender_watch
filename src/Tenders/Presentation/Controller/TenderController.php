<?php

declare(strict_types=1);

namespace App\Tenders\Presentation\Controller;

use App\Tenders\Application\Query\GetTendersQuery;
use App\Tenders\Domain\Repository\TenderRepositoryInterface;
use App\Tenders\Domain\Entity\TenderId;
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
