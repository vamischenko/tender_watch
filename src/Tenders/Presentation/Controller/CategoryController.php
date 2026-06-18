<?php

declare(strict_types=1);

namespace App\Tenders\Presentation\Controller;

use App\Tenders\Domain\Repository\CategoryRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CategoryController
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $categories = $this->categoryRepository->findAll();

        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => array_map(fn($c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'parent_id' => $c->getParentId(),
            ], $categories),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
