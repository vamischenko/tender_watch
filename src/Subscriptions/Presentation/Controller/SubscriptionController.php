<?php

declare(strict_types=1);

namespace App\Subscriptions\Presentation\Controller;

use App\Subscriptions\Application\Command\CreateSubscriptionCommand;
use App\Subscriptions\Application\Command\UpdateSubscriptionCommand;
use App\Subscriptions\Domain\Entity\Subscription;
use App\Subscriptions\Domain\Repository\SubscriptionRepositoryInterface;
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

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('current_user_id');
        $subscriptions = $this->repository->findByUserId($userId);

        return $this->json([
            'success' => true,
            'data' => array_map($this->serialize(...), $subscriptions),
        ]);
    }

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
