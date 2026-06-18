<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Middleware;

use App\Identity\Domain\Repository\ApiTokenRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

final class BearerAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ApiTokenRepositoryInterface $tokenRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Missing or invalid Authorization header');
        }

        $plainToken = substr($authHeader, 7);
        $tokenHash = hash('sha256', $plainToken);
        $token = $this->tokenRepository->findByTokenHash($tokenHash);

        if ($token === null || $token->isExpired()) {
            return $this->unauthorized('Invalid or expired token');
        }

        $user = $this->userRepository->findById($token->getUserId());
        if ($user === null) {
            return $this->unauthorized('User not found');
        }

        return $handler->handle(
            $request
                ->withAttribute('current_user', $user)
                ->withAttribute('current_user_id', $user->getId())
        );
    }

    private function unauthorized(string $message): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(Status::UNAUTHORIZED);
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
