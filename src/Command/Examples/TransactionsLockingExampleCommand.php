<?php

namespace App\Command\Examples;

use App\Entity\User;
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

        $result = $this->entityManager->transactional(function (EntityManager $em) {
            $user = new User();
            $user->name = 'Transactional User';
            $user->email = 'tx-' . uniqid() . '@example.com';
            $user->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
            $user->status = 'active';
            $em->persist($user);
            $em->flush();
            return $user->id;
        });
        $io->success("Transaction committed, user id: {$result}");

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
        } catch (\RuntimeException $e) {
            $rolledBack = true;
        }
        $io->success($rolledBack ? 'Rollback executed as expected' : 'Unexpected: no rollback');

        return Command::SUCCESS;
    }
}
