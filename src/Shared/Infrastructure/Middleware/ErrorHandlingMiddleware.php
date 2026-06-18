<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Http\Status;

final class ErrorHandlingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError(Status::UNPROCESSABLE_ENTITY, $e->getMessage());
        } catch (\DomainException $e) {
            return $this->jsonError(Status::CONFLICT, $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            return $this->jsonError(Status::INTERNAL_SERVER_ERROR, 'Internal Server Error');
        }
    }

    private function jsonError(int $status, string $message): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
            'errors' => [],
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/problem+json');
    }
}
