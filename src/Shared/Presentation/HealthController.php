<?php

declare(strict_types=1);

namespace App\Shared\Presentation;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Psr\SimpleCache\CacheInterface;

final class HealthController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ConnectionInterface $db,
        private readonly CacheInterface $cache,
    ) {
    }

    #[OA\Get(
        path: '/health',
        summary: 'Состояние сервисов',
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Все сервисы работают',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'string',
                            enum: ['ok', 'degraded'],
                            example: 'ok',
                        ),
                        new OA\Property(
                            property: 'checks',
                            properties: [
                                new OA\Property(property: 'database', type: 'string', example: 'ok'),
                                new OA\Property(property: 'cache', type: 'string', example: 'ok'),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 503, description: 'Один или несколько сервисов недоступны'),
        ],
    )]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $checks = [];

        try {
            $this->db->createCommand('SELECT 1')->queryScalar();
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error: ' . $e->getMessage();
        }

        try {
            $this->cache->set('health_check', '1', 5);
            $checks['cache'] = 'ok';
        } catch (\Throwable $e) {
            $checks['cache'] = 'error: ' . $e->getMessage();
        }

        $allOk = !in_array(false, array_map(
            fn($v) => $v === 'ok',
            $checks
        ), true);

        $status = $allOk ? 200 : 503;
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode([
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
