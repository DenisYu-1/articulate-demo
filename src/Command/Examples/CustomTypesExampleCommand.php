<?php

namespace App\Command\Examples;

use App\Entity\User;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:example:custom-types', description: 'Custom types example')]
final class CustomTypesExampleCommand extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = 'types-' . uniqid() . '@example.com';

        $user = new User();
        $user->name = 'Types Demo';
        $user->email = $email;
        $user->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
        $user->status = 'active';

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $found = $this->entityManager->find(User::class, $user->id);
        $io->success("created_at persisted and hydrated as string");
        $io->text("created_at: " . $found->createdAt);

        return Command::SUCCESS;
    }
}
