<?php

namespace App\Command\Examples;

use App\Entity\User;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:example:pagination-sorting-soft-delete', description: 'Pagination, sorting, soft delete example')]
final class PaginationSortingSoftDeleteExampleCommand extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->seedUsers();

        $repo = $this->entityManager->getRepository(User::class);

        $offsetPage = $repo->findBy([], ['id' => 'ASC'], 2, 1);
        $io->text('Offset pagination (limit=2, offset=1): ' . count($offsetPage) . ' users');

        $cursorResult = $repo->findWithCursor(null, 2, ['id' => 'ASC']);
        $items = $cursorResult->getItems();
        $io->text('Cursor pagination (limit=2): ' . count($items) . ' users');
        if ($cursorResult->getNextCursor() !== null) {
            $io->text('Next cursor available');
        }

        $ordered = $repo->findBy([], ['name' => 'DESC'], 3);
        $io->text('Ordered by name DESC (limit 3): ' . implode(', ', array_map(fn (User $u) => $u->name, $ordered)));

        return Command::SUCCESS;
    }

    private function seedUsers(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->name = "Page User " . chr(65 + $i);
            $user->email = "page-{$i}-" . uniqid() . '@example.com';
            $user->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
            $user->status = 'active';
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();
    }
}
