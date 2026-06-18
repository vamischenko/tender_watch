<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Http\Status;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly int $maxRequests = 60,
        private readonly int $windowSeconds = 60,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identifier = $this->resolveIdentifier($request);
        $key = 'rate_limit:' . $identifier;

        $current = (int)($this->cache->get($key, 0));

        if ($current >= $this->maxRequests) {
            $response = $this->responseFactory->createResponse(Status::TOO_MANY_REQUESTS);
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Rate limit exceeded',
            ], JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string)$this->windowSeconds);
        }

        if ($current === 0) {
            $this->cache->set($key, 1, $this->windowSeconds);
        } else {
            $this->cache->set($key, $current + 1, $this->cache->get($key . ':ttl', $this->windowSeconds));
        }

        return $handler->handle($request)
            ->withHeader('X-RateLimit-Limit', (string)$this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string)max(0, $this->maxRequests - $current - 1));
    }

    private function resolveIdentifier(ServerRequestInterface $request): string
    {
        $token = $request->getAttribute('api_token');
        if ($token !== null) {
            return 'token:' . $token->getUserId();
        }

        $serverParams = $request->getServerParams();
        return 'ip:' . ($serverParams['REMOTE_ADDR'] ?? 'unknown');
    }
}
