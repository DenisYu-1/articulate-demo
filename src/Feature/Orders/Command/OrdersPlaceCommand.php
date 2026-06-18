<?php

namespace App\Feature\Orders\Command;

use App\Feature\Orders\Entity\Order;
use App\Feature\Orders\Entity\StockLock;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:orders:place', description: 'Order placement and stock lock demo')]
final class OrdersPlaceCommand extends Command
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
        $suffix = bin2hex(random_bytes(4));

        $io->section('Order placement');

        $customer = $this->createCustomer($suffix);
        $fixture = $this->createProductWithStock($suffix, 'ORDER-PLACE', 8, 129.95);
        $product = $fixture['product'];

        $orderId = $this->entityManager->transactional(function () use ($io, $customer, $product): string {
            $stock = $this->lockStock($product->id);
            $order = $this->createOrder($customer);
            $item = $this->createOrderItem($order, $product, 3);

            if ($stock->stock < $item->quantity) {
                throw new \RuntimeException('Insufficient stock for order placement.');
            }

            $orderId = $this->scheduleOrderGraphWithUuid($order, [$item]);
            $io->text("UUID assigned before flush: {$orderId}");

            $stock->stock -= $item->quantity;
            $this->entityManager->persist($stock);
            $this->entityManager->flush();

            return $orderId;
        });

        $this->entityManager->clear();

        $placed = $this->entityManager->find(Order::class, $orderId);
        $remainingStock = $this->entityManager->find(StockLock::class, $product->id);
        $items = $placed instanceof Order ? $this->entityManager->loadRelation($placed, 'items') : [];

        $io->success(sprintf(
            'Placed order %s with %d item(s); stock now %d',
            $orderId,
            is_countable($items) ? count($items) : 0,
            $remainingStock instanceof StockLock ? $remainingStock->stock : -1,
        ));

        return Command::SUCCESS;
    }
}
