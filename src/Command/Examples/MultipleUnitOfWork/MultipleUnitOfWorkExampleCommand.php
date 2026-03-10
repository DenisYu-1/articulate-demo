<?php

namespace App\Command\Examples\MultipleUnitOfWork;

use App\Entity\User;
use Articulate\Connection;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:example:multiple-unit-of-work', description: 'Multiple unit of work example')]
final class MultipleUnitOfWorkExampleCommand extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = 'muow-' . uniqid() . '@example.com';
        $user = new User();
        $user->name = 'Multiple UoW Demo';
        $user->email = $email;
        $user->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
        $user->status = 'active';

        $this->entityManager->persist($user);
        $io->success("Created user: {$user->name} (id={$user->id})");

        $postEntityManager = $this->entityManager->createUnitOfWork();

        for ($i = 1; $i <= 10; $i++) {
            $post = new Post();
            $post->title = "Post #{$i}";
            $post->content = "Content for post {$i}";
            $post->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
            $post->author = $user;

            $postEntityManager->persist($post);
            $this->entityManager->flush();
            $io->text("Created post #{$i}");
            $postEntityManager->clear();
        }

        return Command::SUCCESS;
    }
}
