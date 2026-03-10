<?php

namespace App\Command\Examples;

use App\Entity\AuditLog;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:example:lifecycle-callbacks', description: 'Lifecycle callbacks example')]
final class LifecycleCallbacksExampleCommand extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $log = new AuditLog();
        $log->action = 'demo_action';

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $io->success("AuditLog persisted with PrePersist setting created_at automatically");
        $io->text("ID: {$log->id}, action: {$log->action}, created_at: {$log->created_at}");

        return Command::SUCCESS;
    }
}
