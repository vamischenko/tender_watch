<?php

declare(strict_types=1);

use App\Notifications\Application\Listener\EnqueueNotificationOnMatch;
use App\Notifications\Infrastructure\Channel\EmailChannel;
use App\Notifications\Infrastructure\Channel\TelegramChannel;
use App\Notifications\Presentation\Controller\NotificationController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

return [
    MailerInterface::class => static function (\Psr\Container\ContainerInterface $c): Mailer {
        $params = $c->get('Yiisoft\Config\Config')->get('params');
        $transport = Transport::fromDsn($params['mailer']['dsn']);
        return new Mailer($transport);
    },

    EmailChannel::class => static function (\Psr\Container\ContainerInterface $c): EmailChannel {
        $params = $c->get('Yiisoft\Config\Config')->get('params');
        return new EmailChannel(
            mailer: $c->get(MailerInterface::class),
            fromAddress: $params['mailer']['from'],
        );
    },

    TelegramChannel::class => static function (\Psr\Container\ContainerInterface $c): TelegramChannel {
        $params = $c->get('Yiisoft\Config\Config')->get('params');
        return new TelegramChannel(
            httpClient: new \GuzzleHttp\Client(),
            requestFactory: new \GuzzleHttp\Psr7\HttpFactory(),
            botToken: $params['telegram']['bot_token'],
        );
    },

    // Массив каналов для воркера
    'notification.channels' => static function (\Psr\Container\ContainerInterface $c): array {
        return [
            $c->get(EmailChannel::class),
            $c->get(TelegramChannel::class),
        ];
    },

    NotificationController::class => static function (\Psr\Container\ContainerInterface $c): NotificationController {
        return new NotificationController(
            db: $c->get(\Yiisoft\Db\Connection\ConnectionInterface::class),
            responseFactory: $c->get(\Psr\Http\Message\ResponseFactoryInterface::class),
        );
    },

    EnqueueNotificationOnMatch::class => static function (\Psr\Container\ContainerInterface $c): EnqueueNotificationOnMatch {
        return new EnqueueNotificationOnMatch(
            publisher: $c->get(\App\Shared\Infrastructure\Queue\RabbitMQPublisher::class),
            userRepository: $c->get(\App\Identity\Domain\Repository\UserRepositoryInterface::class),
            logger: $c->get(\Psr\Log\LoggerInterface::class),
        );
    },
];
