<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Command\LoginCommand;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController
{
    public function __construct(
        private readonly LoginCommand $loginCommand,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array)$request->getParsedBody();
        $email = (string)($body['email'] ?? '');
        $password = (string)($body['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json(['success' => false, 'message' => 'Email and password are required'], 422);
        }

        try {
            $token = $this->loginCommand->execute($email, $password);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 401);
        }

        return $this->json([
            'success' => true,
            'data' => ['token' => $token],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
