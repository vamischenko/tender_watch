<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Middleware\ApiKeyAuthMiddleware;
use App\Shared\Infrastructure\Middleware\BearerAuthMiddleware;
use App\Shared\Infrastructure\Middleware\ErrorHandlingMiddleware;
use App\Shared\Infrastructure\Middleware\RateLimitMiddleware;

return [
    RateLimitMiddleware::class => static function (\Psr\Container\ContainerInterface $c): RateLimitMiddleware {
        $params = $c->get('Yiisoft\Config\Config')->get('params');
        return new RateLimitMiddleware(
            cache: $c->get(\Psr\SimpleCache\CacheInterface::class),
            responseFactory: $c->get(\Psr\Http\Message\ResponseFactoryInterface::class),
            maxRequests: $params['api']['rate_limit_requests'],
            windowSeconds: $params['api']['rate_limit_window'],
        );
    },
];
