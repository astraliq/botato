<?php

declare(strict_types=1);

namespace App\Console;

use App\Application\Queue\ProcessQueueJobHandler;
use App\Domain\Queue\QueueJobRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'queue/consume',
    description: 'Consume jobs from PostgreSQL queue',
)]
final class QueueConsumeCommand extends Command
{
    private bool $shouldStop = false;

    public function __construct(
        private readonly QueueJobRepositoryInterface $queueJobRepository,
        private readonly ProcessQueueJobHandler $processQueueJobHandler,
        private readonly int $defaultSleepSeconds = 1,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Имя очереди', 'default')
            ->addOption(
                'sleep',
                null,
                InputOption::VALUE_REQUIRED,
                'Пауза в секундах при пустой очереди',
                (string) $this->defaultSleepSeconds,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->registerSignalHandlers();

        $queue = (string) $input->getOption('queue');
        $sleepSeconds = max(1, (int) $input->getOption('sleep'));

        $output->writeln(sprintf('Consuming queue "%s" (sleep=%d)', $queue, $sleepSeconds));

        while (!$this->shouldStop) {
            try {
                $job = $this->queueJobRepository->claimNext($queue);
            } catch (\Throwable $exception) {
                $output->writeln(sprintf(
                    '[%s] claim failed: %s',
                    (new \DateTimeImmutable())->format('H:i:s'),
                    $exception->getMessage(),
                ));
                sleep($sleepSeconds);
                continue;
            }

            if ($job === null) {
                sleep($sleepSeconds);
                continue;
            }

            $output->writeln(sprintf(
                '[%s] processing %s (%s)',
                (new \DateTimeImmutable())->format('H:i:s'),
                $job->getId(),
                $job->getJobType(),
            ));

            try {
                $this->processQueueJobHandler->handle($job);
                $output->writeln(sprintf('[%s] done %s', (new \DateTimeImmutable())->format('H:i:s'), $job->getId()));
            } catch (\Throwable $exception) {
                $output->writeln(sprintf(
                    '[%s] failed %s: %s',
                    (new \DateTimeImmutable())->format('H:i:s'),
                    $job->getId(),
                    $exception->getMessage(),
                ));
            }
        }

        $output->writeln('Stopped.');

        return ExitCode::OK;
    }

    private function registerSignalHandlers(): void
    {
        if (!function_exists('pcntl_async_signals') || !function_exists('pcntl_signal')) {
            return;
        }

        pcntl_async_signals(true);

        $stop = function (): void {
            $this->shouldStop = true;
        };

        pcntl_signal(SIGTERM, $stop);
        pcntl_signal(SIGINT, $stop);
    }
}
