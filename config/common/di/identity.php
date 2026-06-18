<?php

declare(strict_types=1);

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\Repository\ApiTokenRepositoryInterface;
use App\Identity\Infrastructure\Persistence\DbUserRepository;
use App\Identity\Infrastructure\Persistence\DbApiTokenRepository;

return [
    UserRepositoryInterface::class => DbUserRepository::class,
    ApiTokenRepositoryInterface::class => DbApiTokenRepository::class,
];
