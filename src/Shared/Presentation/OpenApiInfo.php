<?php

declare(strict_types=1);

namespace App\Shared\Presentation;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        title: 'TenderWatch API',
        description: 'Агрегатор тендеров с подписками и алертами. '
            . 'Тендеры — X-Api-Key, подписки — Bearer токен.',
        contact: new OA\Contact(email: 'noreply@tenderwatch.ru'),
        license: new OA\License(name: 'BSD-3-Clause'),
    ),
    servers: [
        new OA\Server(url: 'http://localhost:8080/api/v1', description: 'Local'),
    ],
)]
#[OA\SecurityScheme(
    securityScheme: 'ApiKey',
    type: 'apiKey',
    description: 'API-ключ в заголовке X-Api-Key',
    name: 'X-Api-Key',
    in: 'header',
)]
#[OA\SecurityScheme(
    securityScheme: 'Bearer',
    type: 'http',
    description: 'Bearer-токен из POST /auth/login',
    scheme: 'bearer',
    bearerFormat: 'opaque',
)]
#[OA\Schema(
    schema: 'Money',
    required: ['amount', 'currency'],
    properties: [
        new OA\Property(property: 'amount', type: 'integer', example: 1500000),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Tender',
    required: ['id', 'title', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'title', type: 'string', example: 'Поставка компьютерной техники'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'budget', ref: '#/components/schemas/Money'),
        new OA\Property(property: 'region', type: 'string', example: 'Москва'),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'deadline_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'is_expired', type: 'boolean'),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['active', 'closed', 'cancelled', 'draft'],
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'FilterCriteria',
    properties: [
        new OA\Property(
            property: 'category_ids',
            type: 'array',
            items: new OA\Items(type: 'string', format: 'uuid'),
        ),
        new OA\Property(property: 'min_budget', type: 'integer', nullable: true, example: 100000),
        new OA\Property(property: 'max_budget', type: 'integer', nullable: true, example: 5000000),
        new OA\Property(
            property: 'regions',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['Москва'],
        ),
        new OA\Property(
            property: 'keywords',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['дорога', 'ремонт'],
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Subscription',
    required: ['id', 'name', 'criteria', 'channels', 'is_active'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Дороги Москвы'),
        new OA\Property(property: 'criteria', ref: '#/components/schemas/FilterCriteria'),
        new OA\Property(
            property: 'channels',
            type: 'array',
            items: new OA\Items(type: 'string', enum: ['email', 'telegram']),
        ),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Category',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Строительство'),
        new OA\Property(property: 'slug', type: 'string', example: 'construction'),
        new OA\Property(property: 'parent_id', type: 'string', format: 'uuid', nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Not found'),
    ],
    type: 'object',
)]
final class OpenApiInfo
{
}
