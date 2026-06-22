<?php

namespace App\Features\BulkImport\Command;

use App\Features\BulkImport\Entity\ImportCategory;
use App\Features\BulkImport\Entity\ImportProduct;
use Articulate\Modules\EntityManager\EntityManager;
use Articulate\Modules\EntityManager\UnitOfWork;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import:run', description: 'Bulk product import and scoped UnitOfWork demo')]
final class BulkImportRunCommand extends Command
{
    private const DEFAULT_COUNT = 5000;
    private const DEFAULT_BATCH_SIZE = 500;

    public function __construct(
        private readonly EntityManager $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Products imported by each strategy', self::DEFAULT_COUNT)
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Products per scoped UnitOfWork batch', self::DEFAULT_BATCH_SIZE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->positiveInt($input->getOption('count'), self::DEFAULT_COUNT);
        $batchSize = $this->positiveInt($input->getOption('batch-size'), self::DEFAULT_BATCH_SIZE);
        $suffix = bin2hex(random_bytes(4));

        $io->section('Bulk import');
        $io->text(sprintf('Importing %d generated products per strategy; scoped batch size=%d.', $count, $batchSize));

        $naiveCategory = $this->createCategory("Bulk Import Naive {$suffix}", "bulk-import-naive-{$suffix}");
        $naive = $this->runNaiveImport($count, $naiveCategory, "IMP-NAIVE-{$suffix}");
        $this->entityManager->clear();

        $scopedCategory = $this->createCategory("Bulk Import Scoped {$suffix}", "bulk-import-scoped-{$suffix}");
        $primaryUnitOfWork = $this->entityManager->getActiveUnitOfWork();
        $scoped = $this->runScopedImport($count, $batchSize, $scopedCategory, "IMP-SCOPED-{$suffix}");
        $primaryStateAfterBatches = $primaryUnitOfWork->getEntityState($scopedCategory)->name;

        $failure = $this->demonstrateBatchFailure($suffix, $batchSize);
        $this->entityManager->clear();

        $io->definitionList(
            ['naive inserted rows' => (string) $naive['inserted']],
            ['naive memory before' => $this->bytes($naive['before'])],
            ['naive memory before flush' => $this->bytes($naive['beforeFlush'])],
            ['naive memory after flush' => $this->bytes($naive['after'])],
            ['naive growth curve' => $this->formatSamples($naive['samples'])],
            ['scoped inserted rows' => (string) $scoped['inserted']],
            ['scoped batches' => (string) $scoped['batches']],
            ['scoped memory before' => $this->bytes($scoped['before'])],
            ['scoped memory after' => $this->bytes($scoped['after'])],
            ['scoped memory peak' => $this->bytes($scoped['peak'])],
            ['scoped growth curve' => $this->formatSamples($scoped['samples'])],
            ['primary category state after scoped batches' => $primaryStateAfterBatches],
            ['failure demo inserted rows' => (string) $failure['inserted']],
            ['failure demo skipped batch' => (string) $failure['failedBatch']],
            ['failure demo rollback check' => $failure['rollbackCheck']],
            ['failure demo resumed batches' => implode(', ', $failure['resumedBatches'])],
        );

        $io->success('Bulk import demo completed');

        return Command::SUCCESS;
    }

    /**
     * @return array{inserted: int, before: int, beforeFlush: int, after: int, samples: array<int, int>}
     */
    private function runNaiveImport(int $count, ImportCategory $category, string $prefix): array
    {
        $before = memory_get_usage();
        $samples = [];
        $sampleEvery = max(1, intdiv($count, 5));

        for ($i = 1; $i <= $count; $i++) {
            $this->entityManager->persist($this->createProduct($prefix, $i, (int) $category->id));

            if ($i === 1 || $i % $sampleEvery === 0 || $i === $count) {
                $samples[$i] = memory_get_usage();
            }
        }

        $beforeFlush = memory_get_usage();
        $this->entityManager->flush();
        $after = memory_get_usage();

        return [
            'inserted' => $this->countProductsByPrefix($prefix),
            'before' => $before,
            'beforeFlush' => $beforeFlush,
            'after' => $after,
            'samples' => $samples,
        ];
    }

    /**
     * @return array{inserted: int, batches: int, before: int, after: int, peak: int, samples: array<int, int>}
     */
    private function runScopedImport(int $count, int $batchSize, ImportCategory $category, string $prefix): array
    {
        $before = memory_get_usage();
        $peak = $before;
        $samples = [];
        $batches = 0;
        $imported = 0;

        while ($imported < $count) {
            $currentBatchSize = min($batchSize, $count - $imported);
            $batchUnitOfWork = $this->entityManager->createUnitOfWork();

            try {
                $this->flushBatch(
                    $batchUnitOfWork,
                    $prefix,
                    (int) $category->id,
                    $imported + 1,
                    $currentBatchSize,
                );
            } finally {
                $this->entityManager->removeUnitOfWork($batchUnitOfWork);
            }
            unset($batchUnitOfWork);
            gc_collect_cycles();

            $imported += $currentBatchSize;
            $batches++;
            $currentMemory = memory_get_usage();
            $peak = max($peak, $currentMemory);
            $samples[$imported] = $currentMemory;
        }

        return [
            'inserted' => $this->countProductsByPrefix($prefix),
            'batches' => $batches,
            'before' => $before,
            'after' => memory_get_usage(),
            'peak' => $peak,
            'samples' => $samples,
        ];
    }

    /**
     * @return array{inserted: int, failedBatch: int, rollbackCheck: string, resumedBatches: string[]}
     */
    private function demonstrateBatchFailure(string $suffix, int $requestedBatchSize): array
    {
        $category = $this->createCategory("Bulk Import Failure {$suffix}", "bulk-import-failure-{$suffix}");
        $prefix = "IMP-FAIL-{$suffix}";
        $batchSize = max(2, min($requestedBatchSize, 25));
        $failedBatch = 2;
        $resumedBatches = [];

        for ($batch = 1; $batch <= 3; $batch++) {
            $batchUnitOfWork = $this->entityManager->createUnitOfWork();

            try {
                $this->flushBatch(
                    $batchUnitOfWork,
                    $prefix,
                    (int) $category->id,
                    (($batch - 1) * $batchSize) + 1,
                    $batchSize,
                    duplicateLastSku: $batch === $failedBatch,
                );
                $resumedBatches[] = "batch {$batch}";
            } catch (\Throwable $e) {
                if ($batch !== $failedBatch) {
                    throw $e;
                }
            } finally {
                $this->entityManager->removeUnitOfWork($batchUnitOfWork);
            }
            unset($batchUnitOfWork);
            gc_collect_cycles();
        }

        $inserted = $this->countProductsByPrefix($prefix);
        $expected = $batchSize * 2;

        return [
            'inserted' => $inserted,
            'failedBatch' => $failedBatch,
            'rollbackCheck' => $inserted === $expected
                ? sprintf('ok: failed batch inserted 0/%d rows', $batchSize)
                : sprintf('unexpected: expected %d rows, found %d', $expected, $inserted),
            'resumedBatches' => $resumedBatches,
        ];
    }

    private function flushBatch(
        UnitOfWork $unitOfWork,
        string $prefix,
        int $categoryId,
        int $start,
        int $count,
        bool $duplicateLastSku = false,
    ): void {
        $this->entityManager->transactional(function () use ($unitOfWork, $prefix, $categoryId, $start, $count, $duplicateLastSku): void {
            for ($i = 0; $i < $count; $i++) {
                $number = $start + $i;
                $product = $this->createProduct($prefix, $number, $categoryId);

                if ($duplicateLastSku && $i === $count - 1) {
                    $product->sku = sprintf('%s-%05d', $prefix, $start);
                    $product->slug = sprintf('%s-%05d-duplicate', strtolower($prefix), $start);
                }

                $unitOfWork->persist($product);
            }

            $this->entityManager->flush();
        });
    }

    private function createCategory(string $name, string $slug): ImportCategory
    {
        $category = new ImportCategory();
        $category->name = $name;
        $category->slug = $slug;

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createProduct(string $prefix, int $number, int $categoryId): ImportProduct
    {
        $product = new ImportProduct();
        $product->sku = sprintf('%s-%05d', $prefix, $number);
        $product->name = sprintf('Imported Product %05d', $number);
        $product->slug = strtolower($product->sku);
        $product->categoryId = $categoryId;
        $product->status = $number % 7 === 0 ? 'draft' : 'active';
        $product->price = 9.99 + (($number % 200) / 2);

        return $product;
    }

    private function countProductsByPrefix(string $prefix): int
    {
        $row = $this->entityManager
            ->getConnection()
            ->executeQuery('SELECT COUNT(*) AS total FROM products WHERE sku LIKE ?', [$prefix . '-%'])
            ->fetch();

        return isset($row['total']) ? (int) $row['total'] : 0;
    }

    private function positiveInt(mixed $value, int $default): int
    {
        if (!is_scalar($value) || $value === '') {
            return $default;
        }

        return max(1, (int) $value);
    }

    /**
     * @param array<int, int> $samples
     */
    private function formatSamples(array $samples): string
    {
        return implode(' | ', array_map(
            fn (int $row, int $bytes): string => sprintf('%d=%s', $row, $this->bytes($bytes)),
            array_keys($samples),
            array_values($samples),
        ));
    }

    private function bytes(int $bytes): string
    {
        return number_format($bytes / 1024, 1) . ' KiB';
    }
}
