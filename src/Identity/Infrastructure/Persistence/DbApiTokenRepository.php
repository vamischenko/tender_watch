<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence;

use App\Identity\Domain\Entity\ApiToken;
use App\Identity\Domain\Repository\ApiTokenRepositoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final class DbApiTokenRepository implements ApiTokenRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function findByTokenHash(string $tokenHash): ?ApiToken
    {
        $row = $this->db->createCommand(
            'SELECT * FROM api_tokens WHERE token_hash = :hash LIMIT 1'
        )->bindValue(':hash', $tokenHash)->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function save(ApiToken $token): void
    {
        $this->db->createCommand()->upsert('api_tokens', [
            'id' => $token->getId(),
            'user_id' => $token->getUserId(),
            'token_hash' => $token->getTokenHash(),
            'expires_at' => $token->getExpiresAt()?->format('Y-m-d H:i:s'),
            'created_at' => $token->getCreatedAt()->format('Y-m-d H:i:s'),
        ], true)->execute();
    }

    public function deleteByUserId(string $userId): void
    {
        $this->db->createCommand()->delete('api_tokens', ['user_id' => $userId])->execute();
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ApiToken
    {
        return ApiToken::restore(
            id: $row['id'],
            userId: $row['user_id'],
            tokenHash: $row['token_hash'],
            expiresAt: $row['expires_at'] ? new \DateTimeImmutable($row['expires_at']) : null,
            createdAt: new \DateTimeImmutable($row['created_at']),
        );
    }
}
