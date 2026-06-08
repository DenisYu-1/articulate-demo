<?php

namespace App\Command\Examples;

use App\Entity\User;
use Articulate\Exceptions\TransactionRequiredException;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:example:transactions-locking', description: 'Transactions and locking example')]
final class TransactionsLockingExampleCommand extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Basic transactional() — auto commit
        $userId = $this->entityManager->transactional(function (EntityManager $em) {
            $user = new User();
            $user->name = 'Transactional User';
            $user->email = 'tx-' . uniqid() . '@example.com';
            $user->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
            $user->status = 'active';
            $em->persist($user);
            $em->flush();
            return $user->id;
        });
        $io->success("Transaction committed, user id: {$userId}");

        // 2. transactional() with exception — auto rollback
        $rolledBack = false;
        try {
            $this->entityManager->transactional(function (EntityManager $em) {
                $user = new User();
                $user->name = 'Rollback User';
                $user->email = 'rollback-' . uniqid() . '@example.com';
                $user->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
                $user->status = 'active';
                $em->persist($user);
                $em->flush();
                throw new \RuntimeException('Intentional rollback');
            });
        } catch (\RuntimeException) {
            $rolledBack = true;
        }
        $io->success($rolledBack ? 'Rollback executed as expected' : 'Unexpected: no rollback');

        // 3. SELECT ... FOR UPDATE inside a transaction
        // Locks the row so concurrent processes cannot modify it until commit.
        $this->entityManager->transactional(function (EntityManager $em) use ($userId, $io) {
            /** @var User $user */
            $user = $em->createQueryBuilder(User::class)
                ->where('id', $userId)
                ->lock()
                ->getResult()[0];

            $io->text("Locked user #{$user->id} ({$user->name}) — status: {$user->status}");

            $user->status = 'suspended';
            $em->persist($user);
            $em->flush();

            $io->success("Updated status to '{$user->status}' within lock, committing.");
        });

        // 4. lock() outside a transaction — must throw TransactionRequiredException
        try {
            $this->entityManager->createQueryBuilder(User::class)
                ->where('id', $userId)
                ->lock()
                ->getResult();

            $io->error('Expected TransactionRequiredException — none thrown.');
        } catch (TransactionRequiredException $e) {
            $io->success("lock() outside transaction correctly threw: {$e->getMessage()}");
        }

        return Command::SUCCESS;
    }
}
