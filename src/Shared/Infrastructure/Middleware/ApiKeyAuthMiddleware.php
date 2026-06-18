<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Middleware;

use App\Identity\Domain\Repository\ApiTokenRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

final class ApiKeyAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ApiTokenRepositoryInterface $tokenRepository,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $apiKey = $request->getHeaderLine('X-Api-Key');

        if ($apiKey === '') {
            return $this->unauthorized('Missing X-Api-Key header');
        }

        $tokenHash = hash('sha256', $apiKey);
        $token = $this->tokenRepository->findByTokenHash($tokenHash);

        if ($token === null || $token->isExpired()) {
            return $this->unauthorized('Invalid or expired API key');
        }

        return $handler->handle($request->withAttribute('api_token', $token));
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
