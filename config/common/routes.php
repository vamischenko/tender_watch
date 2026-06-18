<?php

declare(strict_types=1);

use App\Tenders\Presentation\Controller\TenderController;
use App\Tenders\Presentation\Controller\CategoryController;
use App\Subscriptions\Presentation\Controller\SubscriptionController;
use App\Identity\Presentation\Controller\AuthController;
use App\Shared\Presentation\HealthController;
use Yiisoft\Router\Route;
use Yiisoft\Router\Group;

/**
 * @var array $params
 */

return [
    Route::get('/api/v1/health')->action([HealthController::class, 'index'])->name('health'),

    Group::create('/api/v1')
        ->middleware(\App\Shared\Infrastructure\Middleware\ErrorHandlingMiddleware::class)
        ->routes(
            // Auth
            Route::post('/auth/login')->action([AuthController::class, 'login'])->name('auth.login'),

            // Tenders (API key auth)
            Route::get('/tenders')
                ->middleware(\App\Shared\Infrastructure\Middleware\ApiKeyAuthMiddleware::class)
                ->middleware(\App\Shared\Infrastructure\Middleware\RateLimitMiddleware::class)
                ->action([TenderController::class, 'index'])
                ->name('tenders.index'),

            Route::get('/tenders/{id}')
                ->middleware(\App\Shared\Infrastructure\Middleware\ApiKeyAuthMiddleware::class)
                ->middleware(\App\Shared\Infrastructure\Middleware\RateLimitMiddleware::class)
                ->action([TenderController::class, 'show'])
                ->name('tenders.show'),

            Route::get('/categories')
                ->middleware(\App\Shared\Infrastructure\Middleware\ApiKeyAuthMiddleware::class)
                ->action([CategoryController::class, 'index'])
                ->name('categories.index'),

            // Subscriptions (Bearer auth)
            Route::post('/subscriptions')
                ->middleware(\App\Shared\Infrastructure\Middleware\BearerAuthMiddleware::class)
                ->action([SubscriptionController::class, 'create'])
                ->name('subscriptions.create'),

            Route::get('/subscriptions')
                ->middleware(\App\Shared\Infrastructure\Middleware\BearerAuthMiddleware::class)
                ->action([SubscriptionController::class, 'index'])
                ->name('subscriptions.index'),

            Route::patch('/subscriptions/{id}')
                ->middleware(\App\Shared\Infrastructure\Middleware\BearerAuthMiddleware::class)
                ->action([SubscriptionController::class, 'update'])
                ->name('subscriptions.update'),

            Route::delete('/subscriptions/{id}')
                ->middleware(\App\Shared\Infrastructure\Middleware\BearerAuthMiddleware::class)
                ->action([SubscriptionController::class, 'delete'])
                ->name('subscriptions.delete'),
        ),
];
