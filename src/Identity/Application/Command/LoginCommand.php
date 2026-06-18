<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Domain\Entity\ApiToken;
use App\Identity\Domain\Repository\ApiTokenRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;

final class LoginCommand
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ApiTokenRepositoryInterface $tokenRepository,
    ) {
    }

    public function execute(string $email, string $password): string
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !password_verify($password, $user->getPasswordHash())) {
            throw new \DomainException('Invalid credentials');
        }

        $plainToken = bin2hex(random_bytes(32));
        $token = ApiToken::create(
            userId: $user->getId(),
            plainToken: $plainToken,
            expiresAt: new \DateTimeImmutable('+30 days'),
        );

        $this->tokenRepository->save($token);

        return $plainToken;
    }
}
