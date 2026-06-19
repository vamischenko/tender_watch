<?php

declare(strict_types=1);

namespace App\Notifications\Presentation\Controller;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final class NotificationController
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    #[OA\Get(
        path: '/notifications',
        summary: 'Лог уведомлений текущего пользователя',
        security: [['Bearer' => []]],
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Лог уведомлений',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'tender_id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'tender_title', type: 'string'),
                                    new OA\Property(property: 'subscription_id', type: 'string', format: 'uuid'),
                                    new OA\Property(
                                        property: 'channel_type',
                                        type: 'string',
                                        enum: ['email', 'telegram']
                                    ),
                                    new OA\Property(
                                        property: 'status',
                                        type: 'string',
                                        enum: ['pending', 'sent', 'failed']
                                    ),
                                    new OA\Property(
                                        property: 'sent_at',
                                        type: 'string',
                                        format: 'date-time',
                                        nullable: true
                                    ),
                                    new OA\Property(property: 'error', type: 'string', nullable: true),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object',
                            ),
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
        ],
    )]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $userId = (string)$request->getAttribute('current_user_id');
        $params = $request->getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = min(100, max(1, (int)($params['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $total = (int)$this->db->createCommand(<<<SQL
            SELECT COUNT(nl.id)
            FROM notifications_log nl
            INNER JOIN matches m ON m.id = nl.match_id
            INNER JOIN subscriptions s ON s.id = m.subscription_id
            WHERE s.user_id = :user_id
        SQL, [':user_id' => $userId])->queryScalar();

        $rows = $this->db->createCommand(<<<SQL
            SELECT
                nl.id,
                t.id AS tender_id,
                t.title AS tender_title,
                m.subscription_id,
                nl.channel_type,
                nl.status,
                nl.sent_at,
                nl.error,
                nl.created_at
            FROM notifications_log nl
            INNER JOIN matches m ON m.id = nl.match_id
            INNER JOIN subscriptions s ON s.id = m.subscription_id
            INNER JOIN tenders t ON t.id = m.tender_id
            WHERE s.user_id = :user_id
            ORDER BY nl.created_at DESC
            LIMIT :limit OFFSET :offset
        SQL, [':user_id' => $userId, ':limit' => $perPage, ':offset' => $offset])->queryAll();

        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

        return $this->json([
            'success' => true,
            'data' => array_map(fn(array $row) => [
                'id' => $row['id'],
                'tender_id' => $row['tender_id'],
                'tender_title' => $row['tender_title'],
                'subscription_id' => $row['subscription_id'],
                'channel_type' => $row['channel_type'],
                'status' => $row['status'],
                'sent_at' => $row['sent_at'],
                'error' => $row['error'],
                'created_at' => $row['created_at'],
            ], $rows),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
