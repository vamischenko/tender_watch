<?php

declare(strict_types=1);

namespace App\Console;

use App\Shared\Infrastructure\Migration\M001CreateSchema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'migrate',
    description: 'Run database migrations',
)]
final class MigrateCommand extends Command
{
    public function __construct(private readonly M001CreateSchema $migration)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('down', null, InputOption::VALUE_NONE, 'Rollback migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('down')) {
            $output->writeln('<comment>Rolling back migrations...</comment>');
            $this->migration->down();
            $output->writeln('<info>Rollback complete.</info>');
        } else {
            $output->writeln('<comment>Running migrations...</comment>');
            $this->migration->up();
            $output->writeln('<info>Migrations applied successfully.</info>');
        }

        return ExitCode::OK;
    }
}
