<?php

namespace App\Features\Analytics\Command;

use App\Features\Analytics\Entity\OrderSnapshot;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:analytics:batch', description: 'Analytics batch iteration demo')]
final class AnalyticsBatchCommand extends Command
{
    use AnalyticsCommandSupport;

    public function __construct(
        private readonly EntityManager $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $seed = $this->seedAnalyticsDataset(18);
        $orderIds = $seed['orderIds'];
        $chunkSize = 5;

        $io->section('Analytics batch processing');

        $bounded = $this->processSnapshots($orderIds, $chunkSize, clearEachBatch: true);
        $unbounded = $this->processSnapshots($orderIds, $chunkSize, clearEachBatch: false);
        $this->entityManager->clear();

        $io->definitionList(
            ['bounded batches' => (string) $bounded['batches']],
            ['bounded rows' => (string) $bounded['rows']],
            ['bounded memory before' => $this->bytes($bounded['before'])],
            ['bounded memory after' => $this->bytes($bounded['after'])],
            ['bounded memory peak' => $this->bytes($bounded['peak'])],
            ['unbounded batches' => (string) $unbounded['batches']],
            ['unbounded rows' => (string) $unbounded['rows']],
            ['unbounded memory before' => $this->bytes($unbounded['before'])],
            ['unbounded memory after' => $this->bytes($unbounded['after'])],
            ['unbounded memory peak' => $this->bytes($unbounded['peak'])],
        );

        $io->success('Analytics batch demo completed');

        return Command::SUCCESS;
    }

    /**
     * @param string[] $orderIds
     * @return array{batches: int, rows: int, before: int, after: int, peak: int}
     */
    private function processSnapshots(array $orderIds, int $chunkSize, bool $clearEachBatch): array
    {
        $before = memory_get_usage();
        $peak = $before;
        $rows = 0;
        $batches = 0;

        $chunks = $this->entityManager
            ->createQueryBuilder(OrderSnapshot::class)
            ->whereIn('id', $orderIds)
            ->orderBy('placed_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->chunk($chunkSize);

        foreach ($chunks as $batch) {
            $batches++;
            $rows += count($batch);
            $peak = max($peak, memory_get_usage());

            if ($clearEachBatch) {
                $this->entityManager->clear();
            }
        }

        $after = memory_get_usage();

        return [
            'batches' => $batches,
            'rows' => $rows,
            'before' => $before,
            'after' => $after,
            'peak' => $peak,
        ];
    }

    private function bytes(int $bytes): string
    {
        return number_format($bytes / 1024, 1) . ' KiB';
    }
}
