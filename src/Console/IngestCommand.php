<?php

declare(strict_types=1);

namespace App\Console;

use App\Ingestion\Application\IngestTendersUseCase;
use App\Ingestion\Infrastructure\FakeTenderConnector;
use App\Ingestion\Infrastructure\ZakupkiGovConnector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'tender:ingest',
    description: 'Fetch tenders from external sources',
)]
final class IngestCommand extends Command
{
    public function __construct(
        private readonly IngestTendersUseCase $useCase,
        private readonly FakeTenderConnector $fakeConnector,
        private readonly ZakupkiGovConnector $zakupkiConnector,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'source',
            's',
            InputOption::VALUE_REQUIRED,
            'Source to ingest: fake, zakupki (default: fake)',
            'fake'
        );
        $this->addOption(
            'page-size',
            null,
            InputOption::VALUE_REQUIRED,
            'Items per page',
            '50'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = $input->getOption('source');
        $pageSize = (int)$input->getOption('page-size');

        $connector = match ($source) {
            'zakupki' => $this->zakupkiConnector,
            default => $this->fakeConnector,
        };

        $output->writeln("<info>Starting ingestion from source: {$source}</info>");

        $result = $this->useCase->execute($connector, $pageSize);

        $output->writeln(sprintf(
            '<info>Done. Imported: %d | Skipped: %d | Errors: %d</info>',
            $result->imported,
            $result->skipped,
            $result->errors,
        ));

        return ExitCode::OK;
    }
}
