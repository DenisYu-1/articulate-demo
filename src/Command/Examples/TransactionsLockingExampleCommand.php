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

        // 1. transactional() — auto commit
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

        // 2. transactional() — auto rollback on exception
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

        // 3. Manual beginTransaction / commit / rollback
        // Use when the callback style does not fit — e.g. transaction spans multiple service calls.
        $this->entityManager->beginTransaction();
        try {
            $user = new User();
            $user->name = 'Manual Transaction User';
            $user->email = 'manual-' . uniqid() . '@example.com';
            $user->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
            $user->status = 'active';
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->entityManager->commit();
            $io->success("Manual commit succeeded, user id: {$user->id}");
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        // 4. SELECT ... FOR UPDATE — read-modify-write pattern
        //
        // Without the lock: two concurrent processes both read status='active', both pass
        // the guard, both write — one write is lost or the transition fires twice.
        // With the lock: the second process blocks on the SELECT until the first commits,
        // then reads the already-updated value and skips the guard.
        $this->entityManager->transactional(function (EntityManager $em) use ($userId, $io) {
            /** @var User $user */
            $user = $em->createQueryBuilder(User::class)
                ->where('id', $userId)
                ->lock()
                ->getResult()[0];

            $io->text("Locked user #{$user->id} — status: {$user->status}");

            if ($user->status !== 'active') {
                $io->warning('User already not active — skipping transition.');
                return;
            }

            $user->status = 'suspended';
            $em->persist($user);
            $em->flush();
            $io->success("Status transitioned to '{$user->status}' — committing.");
        });

        // 5. Lock + rollback — the locked write is fully undone
        $statusBeforeRollback = $this->entityManager->find(User::class, $userId)->status;
        try {
            $this->entityManager->transactional(function (EntityManager $em) use ($userId) {
                /** @var User $user */
                $user = $em->createQueryBuilder(User::class)
                    ->where('id', $userId)
                    ->lock()
                    ->getResult()[0];

                $user->status = 'deleted';
                $em->persist($user);
                $em->flush();

                throw new \RuntimeException('Simulated failure after locked write');
            });
        } catch (\RuntimeException) {
        }

        $this->entityManager->clear();
        $statusAfterRollback = $this->entityManager->find(User::class, $userId)->status;
        $io->success(
            "Before rollback: '{$statusBeforeRollback}' — after rollback: '{$statusAfterRollback}' "
            . ($statusBeforeRollback === $statusAfterRollback ? '(lock released, write undone ✓)' : '(unexpected change!)')
        );

        // 6. Savepoints — partial rollback within a transaction
        $this->entityManager->beginTransaction();
        try {
            $user = $this->entityManager->find(User::class, $userId);
            $user->status = 'pending';
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->createSavepoint('before_name_change');

            $user->name = 'Bad Name';
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Roll back only to the savepoint — status change survives, name change does not
            $this->entityManager->getConnection()->rollbackToSavepoint('before_name_change');
            $this->entityManager->getConnection()->releaseSavepoint('before_name_change');

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->entityManager->clear();
        $user = $this->entityManager->find(User::class, $userId);
        $io->success("After savepoint rollback — status: '{$user->status}', name: '{$user->name}' (name change reverted, status kept)");

        // 7. lock() outside a transaction — throws TransactionRequiredException
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
