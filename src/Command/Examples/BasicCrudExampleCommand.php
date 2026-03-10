<?php

namespace App\Command\Examples;

use App\Entity\User;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:example:basic-crud', description: 'Basic CRUD example')]
final class BasicCrudExampleCommand extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = 'demo-' . uniqid() . '@example.com';

        $user = new User();
        $user->name = 'Demo User';
        $user->email = $email;
        $user->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
        $user->status = 'active';

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $io->success("Created user: {$user->name} (id={$user->id})");

        $found = $this->entityManager->find(User::class, $user->id);
        if ($found === null) {
            $io->error("User not found by id={$user->id}");
            return Command::FAILURE;
        }
        $io->text("Found by ID: {$found->name}");

        $found->status = 'updated';
        $this->entityManager->flush();
        $io->success("Updated status to: {$found->status}");

        $this->entityManager->remove($found);
        $this->entityManager->flush();
        $io->success('Removed user (full CRUD cycle completed)');

        return Command::SUCCESS;
    }
}
