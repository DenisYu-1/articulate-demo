<?php

namespace App\Features\Orders\Command;

use App\Features\Orders\Entity\Order;
use App\Features\Orders\Entity\OrderItem;
use App\Features\Orders\Query\OpenOrdersCriteria;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:orders:query', description: 'Order query builder demo')]
final class OrdersQueryCommand extends Command
{
    use OrdersCommandSupport;

    public function __construct(
        private readonly EntityManager $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $seed = $this->seedOrders();
        $orderIds = $seed['orderIds'];

        $io->section('Order queries');

        $totals = $this->entityManager
            ->createQueryBuilder()
            ->select('o.id')
            ->sum('oi.quantity * oi.unit_price', 'total')
            ->from('orders', 'o')
            ->join('order_items oi', 'oi.order_id = o.id')
            ->whereIn('o.id', $orderIds)
            ->groupBy('o.id')
            ->orderBy('o.id', 'ASC')
            ->getResult();
        $io->text('INNER JOIN aggregate order totals: ' . count($totals));

        $itemCounts = $this->entityManager
            ->createQueryBuilder()
            ->select('o.id')
            ->count('oi.id', 'item_count')
            ->from('orders', 'o')
            ->leftJoin('order_items oi', 'oi.order_id = o.id')
            ->whereIn('o.id', $orderIds)
            ->groupBy('o.id')
            ->orderBy('o.id', 'ASC')
            ->getResult();
        $io->text('LEFT JOIN item counts: ' . count($itemCounts));

        $subquery = $this->entityManager
            ->createQueryBuilder()
            ->select('1')
            ->from('orders', 'o')
            ->whereRaw('o.customer_id = c.id')
            ->where('o.status', 'open');

        $customersWithOpenOrders = $this->entityManager
            ->createQueryBuilder()
            ->select('c.id')
            ->from('customers', 'c')
            ->where('c.id', $seed['customerId'])
            ->whereExists($subquery)
            ->getResult();
        $io->text('whereExists(open orders by customer): ' . count($customersWithOpenOrders));

        $openOrders = $this->entityManager
            ->createQueryBuilder()
            ->select('o.id')
            ->from('orders', 'o')
            ->whereIn('o.id', $orderIds)
            ->apply(new OpenOrdersCriteria('o.status'))
            ->orderBy('o.id', 'ASC')
            ->getResult();
        $io->text('OpenOrdersCriteria: ' . count($openOrders));

        $emptyQb = $this->entityManager
            ->createQueryBuilder(Order::class)
            ->whereIn('id', [])
            ->orderBy('id', 'ASC');
        $io->text('whereIn(id, []) SQL: ' . $emptyQb->getSQL());
        $io->text('whereIn(id, []) rows: ' . count($emptyQb->getResult()));

        $implicitNullQb = $this->entityManager
            ->createQueryBuilder(Order::class)
            ->whereIn('id', $orderIds)
            ->where('shipped_at', null);
        $explicitNullQb = $this->entityManager
            ->createQueryBuilder(Order::class)
            ->whereIn('id', $orderIds)
            ->whereNull('shipped_at');
        $io->text('where(shipped_at, null) SQL: ' . $implicitNullQb->getSQL());
        $io->text('whereNull(shipped_at) rows: ' . count($explicitNullQb->getResult()));

        $safeExpensiveItems = $this->entityManager
            ->createQueryBuilder()
            ->select('oi.order_id', 'oi.product_id')
            ->from('order_items', 'oi')
            ->whereIn('oi.order_id', $orderIds)
            ->whereRaw('oi.unit_price > ?', 100)
            ->getResult();
        $io->text('whereRaw parameterized rows: ' . count($safeExpensiveItems));
        $io->text('whereRaw concatenated example skipped: unsafe input must never be interpolated.');

        $orders = $this->entityManager
            ->createQueryBuilder(Order::class)
            ->whereIn('id', $orderIds)
            ->orderBy('id', 'ASC')
            ->getResult(Order::class);
        $lazyItemCount = 0;
        $lazyLoads = 0;
        foreach ($orders as $order) {
            $items = $this->entityManager->loadRelation($order, 'items');
            $lazyLoads++;
            $lazyItemCount += is_countable($items) ? count($items) : 0;
        }
        $io->text("Explicit relation loading: {$lazyLoads} item queries, {$lazyItemCount} item(s)");

        $batchedItems = $this->entityManager
            ->createQueryBuilder(OrderItem::class)
            ->whereIn('order_id', $orderIds)
            ->orderBy('id', 'ASC')
            ->getResult(OrderItem::class);
        $io->text('Manual eager batch item query: ' . count($batchedItems) . ' item(s)');

        $io->success('Order query demo completed');

        return Command::SUCCESS;
    }

    /**
     * @return array{customerId: int, orderIds: string[]}
     */
    private function seedOrders(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $customer = $this->createCustomer($suffix, 'Orders Query Customer');
        $firstFixture = $this->createProductWithStock($suffix, 'ORDER-Q-A', 12, 199.00);
        $secondFixture = $this->createProductWithStock($suffix, 'ORDER-Q-B', 12, 49.50);

        $firstOrder = $this->createOrder($customer, 'open');
        $this->scheduleOrderGraph($firstOrder, [
            $this->createOrderItem($firstOrder, $firstFixture['product'], 2),
            $this->createOrderItem($firstOrder, $secondFixture['product'], 1),
        ]);

        $secondOrder = $this->createOrder($customer, 'shipped');
        $this->scheduleOrderGraph($secondOrder, [
            $this->createOrderItem($secondOrder, $secondFixture['product'], 4),
        ]);

        $this->entityManager->flush();

        $firstOrderId = $firstOrder->id;
        $secondOrderId = $secondOrder->id;
        if ($firstOrderId === null || $secondOrderId === null) {
            throw new \RuntimeException('Order ids were not assigned by flush().');
        }

        $this->entityManager->clear();

        return ['customerId' => $customer->id, 'orderIds' => [$firstOrderId, $secondOrderId]];
    }
}
