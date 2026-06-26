<?php

namespace App\Features\Analytics\Command;

use App\Features\Analytics\Diagnostics\CountingQueryLogger;
use App\Features\Analytics\Entity\OrderItemSnapshot;
use App\Features\Analytics\Entity\OrderSnapshot;
use App\Features\Analytics\Entity\ProductSnapshot;
use App\Features\Analytics\Query\DateRangeCriteria;
use App\Features\Analytics\Query\StatusFilterCriteria;
use Articulate\Modules\EntityManager\EntityManager;
use Articulate\Modules\EntityManager\ObjectHydrator;
use Articulate\Modules\EntityManager\PartialHydrator;
use Articulate\Modules\EntityManager\ScalarHydrator;
use Articulate\Modules\QueryBuilder\QueryBuilder;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:analytics:report', description: 'Analytics projection and reporting demo')]
final class AnalyticsReportCommand extends Command
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
        $seed = $this->seedAnalyticsDataset();

        $io->section('Analytics report');

        $revenue = $this->revenueByCategory($this->entityManager, $seed['orderIds']);
        $io->text('Revenue by category: ' . $this->summarizeRevenueRows($revenue));

        $topProducts = $this->topProducts($this->entityManager, $seed['orderIds']);
        $io->text('Top products: ' . implode(', ', array_map(
            fn (array $row): string => sprintf('%s=%d units', $row['product_name'], (int) $row['units_sold']),
            $topProducts,
        )));

        $statusCounts = $this->orderCountsByStatus($seed['orderIds'], $seed['from'], $seed['to']);
        $io->text('Status counts via composed criteria: ' . implode(', ', array_map(
            fn (array $row): string => sprintf('%s=%d', $row['status'], (int) $row['orders']),
            $statusCounts,
        )));

        $this->demonstrateProjectionEntities($io, $seed['orderIds'], $seed['productIds']);
        $this->demonstrateHydrators($io, $seed['orderIds']);
        $this->demonstrateResultCache($io, $seed['orderIds'], $seed['firstItemId'], $seed['suffix']);
        $this->demonstrateSecondLevelCacheScope($io, $seed['orderIds']);

        $io->success('Analytics report demo completed');

        return Command::SUCCESS;
    }

    /**
     * @param string[] $orderIds
     * @return array<int, array<string, mixed>>
     */
    private function revenueByCategory(EntityManager $em, array $orderIds, ?string $cacheKey = null): array
    {
        $qb = $em
            ->createQueryBuilder()
            ->select('p.category_id', 'c.name as category_name')
            ->sum('oi.quantity * oi.unit_price', 'revenue')
            ->avg('oi.unit_price', 'avg_unit_price')
            ->max('oi.unit_price', 'max_unit_price')
            ->from('order_items', 'oi')
            ->join('products p', 'p.id = oi.product_id')
            ->leftJoin('categories c', 'c.id = p.category_id')
            ->whereIn('oi.order_id', $orderIds)
            ->groupBy('p.category_id', 'c.name')
            ->orderBy('p.category_id', 'ASC');

        if ($cacheKey !== null) {
            $qb->enableResultCache(300, $cacheKey);
        }

        return $qb->getResult();
    }

    /**
     * @param string[] $orderIds
     * @return array<int, array<string, mixed>>
     */
    private function topProducts(EntityManager $em, array $orderIds): array
    {
        return $em
            ->createQueryBuilder()
            ->select('oi.product_id', 'p.product_name as product_name')
            ->sum('oi.quantity', 'units_sold')
            ->sum('oi.quantity * oi.unit_price', 'revenue')
            ->from('order_items', 'oi')
            ->join('products p', 'p.id = oi.product_id')
            ->whereIn('oi.order_id', $orderIds)
            ->groupBy('oi.product_id', 'p.product_name')
            ->orderBy('units_sold', 'DESC')
            ->getResult();
    }

    /**
     * @param string[] $orderIds
     * @return array<int, array<string, mixed>>
     */
    private function orderCountsByStatus(array $orderIds, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->entityManager
            ->createQueryBuilder(OrderSnapshot::class)
            ->select('status')
            ->count('id', 'orders')
            ->whereIn('id', $orderIds)
            ->apply(new DateRangeCriteria('placed_at', $from, $to))
            ->apply(new StatusFilterCriteria('status', ['open', 'shipped', 'cancelled']))
            ->groupBy('status')
            ->orderBy('status', 'ASC')
            ->getResult();
    }

    /**
     * @param string[] $orderIds
     * @param int[] $productIds
     */
    private function demonstrateProjectionEntities(SymfonyStyle $io, array $orderIds, array $productIds): void
    {
        $products = $this->entityManager
            ->createQueryBuilder(ProductSnapshot::class)
            ->whereIn('id', $productIds)
            ->orderBy('id', 'ASC')
            ->getResult();

        $orders = $this->entityManager
            ->createQueryBuilder(OrderSnapshot::class)
            ->whereIn('id', $orderIds)
            ->orderBy('placed_at', 'ASC')
            ->limit(3)
            ->getResult();

        $items = $this->entityManager
            ->createQueryBuilder(OrderItemSnapshot::class)
            ->whereIn('order_id', $orderIds)
            ->orderBy('id', 'ASC')
            ->limit(3)
            ->getResult();

        $io->text(sprintf(
            'Projection entities: %d ProductSnapshot row(s), %d OrderSnapshot sample row(s), %d OrderItemSnapshot sample row(s)',
            count($products),
            count($orders),
            count($items),
        ));
        $io->text('Analytics columns: ' . $this->summarizeAnalyticsColumns($products, $orders, $items));
    }

    /**
     * @param string[] $orderIds
     */
    private function demonstrateHydrators(SymfonyStyle $io, array $orderIds): void
    {
        try {
            $scalarRows = (new QueryBuilder(
                $this->entityManager->getConnection(),
                new ScalarHydrator(),
                $this->entityManager->getMetadataRegistry(),
            ))
                ->raw('SELECT COUNT(*) AS total FROM orders WHERE id IN (?)', [$orderIds])
                ->getResult(OrderSnapshot::class);
            $io->text('ScalarHydrator standalone raw count: ' . (int) ($scalarRows[0] ?? 0));
        } catch (\Throwable $e) {
            $io->warning('ScalarHydrator demo failed: ' . $this->shortError($e));
        }

        $hydrator = $this->entityManager->getHydrator();
        if (!$hydrator instanceof ObjectHydrator) {
            $io->warning('PartialHydrator demo skipped: default ObjectHydrator is not available.');

            return;
        }

        try {
            $partialRows = $this->entityManager
                ->createQueryBuilder(OrderSnapshot::class)
                ->raw('SELECT id, status FROM orders WHERE id IN (?) ORDER BY id ASC', [$orderIds])
                ->setHydrator(new PartialHydrator($hydrator))
                ->getResult(OrderSnapshot::class);
            $io->text('PartialHydrator raw projection rows: ' . count($partialRows));
        } catch (\Throwable $e) {
            $io->warning('PartialHydrator demo failed: ' . $this->shortError($e));
        }
    }

    /**
     * @param string[] $orderIds
     */
    private function demonstrateResultCache(SymfonyStyle $io, array $orderIds, int $firstItemId, string $suffix): void
    {
        $logger = new CountingQueryLogger();
        $cachedEm = $this->createInstrumentedEntityManager($logger, new ArrayAdapter());
        $cacheKey = "analytics_revenue_{$suffix}";

        $logger->reset();
        $coldRows = $this->revenueByCategory($cachedEm, $orderIds, $cacheKey);
        $coldQueries = $logger->count();

        $logger->reset();
        $warmRows = $this->revenueByCategory($cachedEm, $orderIds, $cacheKey);
        $warmQueries = $logger->count();

        $this->entityManager->getConnection()->executeQuery(
            'UPDATE order_items SET quantity = quantity + 10 WHERE id = ?',
            [$firstItemId],
        );

        $logger->reset();
        $staleRows = $this->revenueByCategory($cachedEm, $orderIds, $cacheKey);
        $staleQueries = $logger->count();

        $logger->reset();
        $freshRows = $this->revenueByCategory($cachedEm, $orderIds);
        $freshQueries = $logger->count();

        $io->definitionList(
            ['result cache cold queries' => (string) $coldQueries],
            ['result cache warm queries' => (string) $warmQueries],
            ['cached total before update' => $this->money($this->sumRevenue($coldRows))],
            ['cached total after update within TTL' => $this->money($this->sumRevenue($staleRows)) . " ({$staleQueries} queries)"],
            ['fresh total after update' => $this->money($this->sumRevenue($freshRows)) . " ({$freshQueries} query)"],
            ['warm rows reused' => count($warmRows) === count($coldRows) ? 'yes' : 'no'],
        );
    }

    /**
     * @param string[] $orderIds
     */
    private function demonstrateSecondLevelCacheScope(SymfonyStyle $io, array $orderIds): void
    {
        $logger = new CountingQueryLogger();
        $l2Em = $this->createInstrumentedEntityManager($logger, secondLevelCache: new ArrayAdapter());
        $orderId = $orderIds[0];

        $logger->reset();
        $l2Em->find(OrderSnapshot::class, $orderId);
        $coldFindQueries = $logger->count();

        $l2Em->clear();
        $logger->reset();
        $l2Em->find(OrderSnapshot::class, $orderId);
        $warmFindQueries = $logger->count();

        foreach ($orderIds as $id) {
            $l2Em->find(OrderSnapshot::class, $id);
        }
        $l2Em->clear();

        $logger->reset();
        $repositoryRows = $l2Em
            ->getRepository(OrderSnapshot::class)
            ->findBy(['id' => $orderIds], ['id' => 'ASC']);
        $findByQueries = $logger->count();

        $l2Em->clear();
        $logger->reset();
        $queryRows = $l2Em
            ->createQueryBuilder(OrderSnapshot::class)
            ->whereIn('id', $orderIds)
            ->orderBy('id', 'ASC')
            ->getResult();
        $getResultQueries = $logger->count();

        $io->definitionList(
            ['L2 find() cold queries' => (string) $coldFindQueries],
            ['L2 find() warm queries' => (string) $warmFindQueries],
            ['findBy() after L2 warm' => count($repositoryRows) . " row(s), {$findByQueries} query"],
            ['getResult() after L2 warm' => count($queryRows) . " row(s), {$getResultQueries} query"],
            ['chunk() L2 scope' => method_exists($l2Em->createQueryBuilder(OrderSnapshot::class), 'chunk')
                ? 'available; app:analytics:batch covers it'
                : 'not available in this installed library'],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function summarizeRevenueRows(array $rows): string
    {
        return implode(', ', array_map(
            fn (array $row): string => sprintf('%s=%s', $row['category_name'], $this->money((float) $row['revenue'])),
            $rows,
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function sumRevenue(array $rows): float
    {
        return array_sum(array_map(fn (array $row): float => (float) ($row['revenue'] ?? 0), $rows));
    }

    /**
     * @param ProductSnapshot[] $products
     * @param OrderSnapshot[] $orders
     * @param OrderItemSnapshot[] $items
     */
    private function summarizeAnalyticsColumns(array $products, array $orders, array $items): string
    {
        $families = array_values(array_unique(array_filter(array_map(
            fn (ProductSnapshot $product): ?string => $product->analyticsFamily,
            $products,
        ))));
        $channels = array_values(array_unique(array_filter(array_map(
            fn (OrderSnapshot $order): ?string => $order->analyticsChannel,
            $orders,
        ))));
        $margin = array_sum(array_map(
            fn (OrderItemSnapshot $item): float => (float) ($item->marginAmount ?? 0.0),
            $items,
        ));

        return sprintf(
            'families=%s; channels=%s; sample margin=%s',
            implode('/', $families),
            implode('/', $channels),
            $this->money($margin),
        );
    }

    private function money(float $value): string
    {
        return '$' . number_format($value, 2, '.', '');
    }

    private function shortError(\Throwable $e): string
    {
        $message = preg_replace('/\s+/', ' ', $e->getMessage());

        return mb_strimwidth($message ?? '', 0, 180, '...');
    }
}
