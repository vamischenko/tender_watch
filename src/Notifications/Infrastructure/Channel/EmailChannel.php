<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Channel;

use App\Notifications\Domain\NotificationChannelInterface;
use App\Notifications\Domain\NotificationMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class EmailChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress,
    ) {
    }

    public function supports(string $channelType): bool
    {
        return $channelType === 'email';
    }

    public function send(NotificationMessage $message): void
    {
        $email = (new Email())
            ->from($this->fromAddress)
            ->to($message->target)
            ->subject($message->subject)
            ->text($message->body);

        $this->mailer->send($email);
    }
}
