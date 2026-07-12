<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'migrate/up',
    description: 'Apply database migrations',
)]
final class MigrateUpCommand extends Command
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connection
            ->createCommand(
                'CREATE TABLE IF NOT EXISTS migration (
                    version VARCHAR(255) PRIMARY KEY,
                    applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
                )',
            )
            ->execute();

        $migrationDirectory = dirname(__DIR__, 2) . '/migrations';
        $migrationFiles = glob($migrationDirectory . '/M*.php') ?: [];
        sort($migrationFiles);

        foreach ($migrationFiles as $migrationFile) {
            $version = basename($migrationFile, '.php');
            $isApplied = $this->connection
                ->createCommand('SELECT 1 FROM migration WHERE version = :version')
                ->bindValue(':version', $version)
                ->queryScalar();

            if ($isApplied !== false && $isApplied !== null) {
                continue;
            }

            /** @psalm-suppress UnresolvableInclude */
            $migrationDefinition = require $migrationFile;
            if (!is_array($migrationDefinition) || !is_callable($migrationDefinition['up'] ?? null)) {
                throw new \RuntimeException("Invalid migration file: {$migrationFile}");
            }

            /** @var callable(ConnectionInterface): void $migrationUp */
            $migrationUp = $migrationDefinition['up'];
            $migrationUp($this->connection);

            $this->connection
                ->createCommand('INSERT INTO migration (version) VALUES (:version)')
                ->bindValue(':version', $version)
                ->execute();

            $output->writeln("Applied: {$version}");
        }

        $output->writeln('Migrations completed.');

        return ExitCode::OK;
    }
}
