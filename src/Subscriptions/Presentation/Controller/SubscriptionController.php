<?php

declare(strict_types=1);

namespace App\Subscriptions\Presentation\Controller;

use App\Subscriptions\Application\Command\CreateSubscriptionCommand;
use App\Subscriptions\Application\Command\UpdateSubscriptionCommand;
use App\Subscriptions\Domain\Entity\Subscription;
use App\Subscriptions\Domain\Repository\SubscriptionRepositoryInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SubscriptionController
{
    public function __construct(
        private readonly CreateSubscriptionCommand $createCommand,
        private readonly UpdateSubscriptionCommand $updateCommand,
        private readonly SubscriptionRepositoryInterface $repository,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    #[OA\Get(
        path: '/subscriptions',
        summary: 'Список подписок текущего пользователя',
        security: [['Bearer' => []]],
        tags: ['Subscriptions'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список подписок',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Subscription'),
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
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('current_user_id');
        $subscriptions = $this->repository->findByUserId($userId);

        return $this->json([
            'success' => true,
            'data' => array_map($this->serialize(...), $subscriptions),
        ]);
    }

    #[OA\Post(
        path: '/subscriptions',
        summary: 'Создать подписку',
        security: [['Bearer' => []]],
        tags: ['Subscriptions'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'criteria', 'channels'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Дороги Москвы'),
                    new OA\Property(property: 'criteria', ref: '#/components/schemas/FilterCriteria'),
                    new OA\Property(
                        property: 'channels',
                        type: 'array',
                        items: new OA\Items(type: 'string', enum: ['email', 'telegram']),
                        example: ['email'],
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Подписка создана',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Subscription'),
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
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('current_user_id');
        $body = (array)$request->getParsedBody();

        $subscription = $this->createCommand->execute(
            userId: $userId,
            name: (string)($body['name'] ?? ''),
            criteria: (array)($body['criteria'] ?? []),
            channels: (array)($body['channels'] ?? ['email']),
        );

        return $this->json(['success' => true, 'data' => $this->serialize($subscription)], 201);
    }

    #[OA\Patch(
        path: '/subscriptions/{id}',
        summary: 'Обновить подписку',
        security: [['Bearer' => []]],
        tags: ['Subscriptions'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'UUID подписки',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'criteria', ref: '#/components/schemas/FilterCriteria'),
                    new OA\Property(
                        property: 'channels',
                        type: 'array',
                        items: new OA\Items(type: 'string', enum: ['email', 'telegram']),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Подписка обновлена',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Subscription'),
                    ],
                ),
            ),
            new OA\Response(
                response: 403,
                description: 'Нет доступа',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Подписка не найдена',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ],
    )]
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $userId = $request->getAttribute('current_user_id');
        $body = (array)$request->getParsedBody();

        try {
            $subscription = $this->updateCommand->execute(
                id: $id,
                currentUserId: $userId,
                criteria: isset($body['criteria']) ? (array)$body['criteria'] : null,
                channels: isset($body['channels']) ? (array)$body['channels'] : null,
            );
        } catch (\DomainException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 403;
            return $this->json(['success' => false, 'message' => $e->getMessage()], $status);
        }

        return $this->json(['success' => true, 'data' => $this->serialize($subscription)]);
    }

    #[OA\Delete(
        path: '/subscriptions/{id}',
        summary: 'Удалить подписку',
        security: [['Bearer' => []]],
        tags: ['Subscriptions'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'UUID подписки',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Подписка удалена',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'null'),
                    ],
                ),
            ),
            new OA\Response(
                response: 403,
                description: 'Нет доступа',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Подписка не найдена',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ],
    )]
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $userId = $request->getAttribute('current_user_id');

        $subscription = $this->repository->findById($id);
        if ($subscription === null) {
            return $this->json(['success' => false, 'message' => 'Not found'], 404);
        }
        if ($subscription->getUserId() !== $userId) {
            return $this->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $this->repository->delete($id);

        return $this->json(['success' => true, 'data' => null]);
    }

    /** @return array<string, mixed> */
    private function serialize(Subscription $s): array
    {
        return [
            'id' => $s->getId(),
            'name' => $s->getName(),
            'criteria' => $s->getCriteria()->toArray(),
            'channels' => $s->getChannels(),
            'is_active' => $s->isActive(),
            'created_at' => $s->getCreatedAt()->format(\DateTimeInterface::ATOM),
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
