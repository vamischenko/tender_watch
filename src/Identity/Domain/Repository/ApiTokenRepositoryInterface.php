<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Entity\ApiToken;

interface ApiTokenRepositoryInterface
{
    public function findByTokenHash(string $tokenHash): ?ApiToken;
    public function save(ApiToken $token): void;
    public function deleteByUserId(string $userId): void;
}
