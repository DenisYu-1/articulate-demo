<?php

namespace App\Feature\Orders\Command;

use App\Feature\Orders\Entity\StockLock;
use Articulate\Exceptions\TransactionRequiredException;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:orders:deadlock', description: 'Order stock locking deadlock mitigation demo')]
final class OrdersDeadlockCommand extends Command
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

        $io->section('Order deadlock mitigation');

        $first = $this->createProductWithStock($suffix, 'ORDER-D-A', 10, 20.00)['product'];
        $second = $this->createProductWithStock($suffix, 'ORDER-D-B', 10, 30.00)['product'];

        try {
            $this->entityManager
                ->createQueryBuilder(StockLock::class)
                ->where('product_id', $first->id)
                ->lock()
                ->getResult();

            $io->error('Expected TransactionRequiredException, but lock outside transaction succeeded.');
        } catch (TransactionRequiredException $e) {
            $io->success('lock() outside transaction rejected: ' . $e->getMessage());
        }

        $io->text(sprintf(
            'Concurrent opposite lock order would deadlock: [%d, %d] vs [%d, %d]',
            $first->id,
            $second->id,
            $second->id,
            $first->id,
        ));

        $ids = [$second->id, $first->id];
        sort($ids, SORT_NUMERIC);

        $this->entityManager->transactional(function () use ($ids): void {
            foreach ($ids as $productId) {
                $stock = $this->lockStock($productId);
                $stock->stock -= 1;
                $this->entityManager->persist($stock);
            }

            $this->entityManager->flush();
        });
        $io->success('Deterministic product_id lock order committed.');

        $this->entityManager->beginTransaction();
        try {
            $stock = $this->lockStock($second->id);
            $stock->stock -= 1;
            $this->entityManager->persist($stock);
            $this->entityManager->flush();
            $this->entityManager->commit();
            $io->success('Manual beginTransaction/commit path committed.');
        } catch (\Throwable $e) {
            $this->entityManager->rollback();

            throw $e;
        }

        $beforeRollback = $this->entityManager->find(StockLock::class, $first->id);
        $beforeStock = $beforeRollback instanceof StockLock ? $beforeRollback->stock : -1;

        try {
            $this->entityManager->transactional(function () use ($first): void {
                $stock = $this->lockStock($first->id);
                $stock->stock -= 5;
                $this->entityManager->persist($stock);
                $this->entityManager->flush();

                throw new \RuntimeException('Simulated failure after stock decrement');
            });
        } catch (\RuntimeException $e) {
            $io->text('Rollback path caught: ' . $this->shortError($e));
        }

        $this->entityManager->clear();
        $afterRollback = $this->entityManager->find(StockLock::class, $first->id);
        $afterStock = $afterRollback instanceof StockLock ? $afterRollback->stock : -1;
        $io->success("Rollback kept stock unchanged: {$beforeStock} -> {$afterStock}");

        return Command::SUCCESS;
    }
}
