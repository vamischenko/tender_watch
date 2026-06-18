<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Channel;

use App\Notifications\Domain\NotificationChannelInterface;
use App\Notifications\Domain\NotificationMessage;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class TelegramChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly string $botToken,
    ) {
    }

    public function supports(string $channelType): bool
    {
        return $channelType === 'telegram';
    }

    public function send(NotificationMessage $message): void
    {
        if ($message->target === '') {
            return;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $body = json_encode([
            'chat_id' => $message->target,
            'text' => "*{$message->subject}*\n\n{$message->body}",
            'parse_mode' => 'Markdown',
        ], JSON_THROW_ON_ERROR);

        $request = $this->requestFactory->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/json');
        $request->getBody()->write($body);

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Telegram API error: ' . $response->getBody()->getContents());
        }
    }
}
