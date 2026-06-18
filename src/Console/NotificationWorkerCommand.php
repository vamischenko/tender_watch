<?php

declare(strict_types=1);

namespace App\Console;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Notifications\Domain\NotificationChannelInterface;
use App\Notifications\Domain\NotificationMessage;
use App\Shared\Infrastructure\Queue\QueueMessage;
use App\Shared\Infrastructure\Queue\RabbitMQConsumer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'worker:notifications',
    description: 'Process notification jobs from the queue',
)]
final class NotificationWorkerCommand extends Command
{
    /** @param NotificationChannelInterface[] $channels */
    public function __construct(
        private readonly RabbitMQConsumer $consumer,
        private readonly UserRepositoryInterface $userRepository,
        private readonly array $channels,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Notification worker started. Waiting for jobs...</info>');

        $this->consumer->consume(
            'notifications',
            function (QueueMessage $message) use ($output): bool {
                if ($message->type !== 'send_notification') {
                    return true;
                }

                $payload = $message->payload;
                $user = $this->userRepository->findById($payload['user_id'] ?? '');

                if ($user === null) {
                    $output->writeln('<comment>User not found: ' . ($payload['user_id'] ?? '') . '</comment>');
                    return true;
                }

                $channelType = $payload['channel_type'] ?? 'email';
                $target = match ($channelType) {
                    'email' => $user->getEmail(),
                    'telegram' => $user->getTelegramChatId() ?? '',
                    default => '',
                };

                if ($target === '') {
                    return true;
                }

                $notifMessage = new NotificationMessage(
                    userId: $user->getId(),
                    channelType: $channelType,
                    target: $target,
                    subject: $payload['subject'] ?? 'Новый тендер',
                    body: $payload['body'] ?? '',
                    tenderId: $payload['tender_id'] ?? '',
                    subscriptionId: $payload['subscription_id'] ?? '',
                );

                foreach ($this->channels as $channel) {
                    if ($channel->supports($channelType)) {
                        $channel->send($notifMessage);
                        $output->writeln("<info>Sent {$channelType} to {$target}</info>");
                        return true;
                    }
                }

                $output->writeln("<comment>No channel handler for: {$channelType}</comment>");
                return true;
            }
        );

        return ExitCode::OK;
    }
}
