<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final class DbUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function findById(string $id): ?User
    {
        $row = $this->db->createCommand(
            'SELECT * FROM users WHERE id = :id LIMIT 1'
        )->bindValue(':id', $id)->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->db->createCommand(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        )->bindValue(':email', $email)->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function save(User $user): void
    {
        $this->db->createCommand()->upsert('users', [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'telegram_chat_id' => $user->getTelegramChatId(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
        ], true)->execute();
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): User
    {
        return User::restore(
            id: $row['id'],
            email: $row['email'],
            passwordHash: $row['password_hash'],
            telegramChatId: $row['telegram_chat_id'],
            createdAt: new \DateTimeImmutable($row['created_at']),
        );
    }
}
