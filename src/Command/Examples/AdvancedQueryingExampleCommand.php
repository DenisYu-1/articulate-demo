<?php

namespace App\Command\Examples;

use App\Entity\User;
use Articulate\Modules\EntityManager\EntityManager;
use Articulate\Modules\QueryBuilder\QueryBuilder;
use Articulate\Modules\Repository\Criteria\CriteriaInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:example:advanced-querying', description: 'Advanced querying example')]
final class AdvancedQueryingExampleCommand extends Command
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

        $qb = $this->entityManager->createQueryBuilder(User::class);
        $active = $qb->where('status', 'active')->limit(5)->getResult();
        $io->text('Active users (where + limit): ' . count($active));

        $qb2 = $this->entityManager->createQueryBuilder(User::class);
        $qb2->whereIn('status', ['active', 'pending']);
        $inResult = $qb2->limit(10)->getResult();
        $io->text('Users with status IN (active, pending): ' . count($inResult));

        $qb3 = $this->entityManager->createQueryBuilder(User::class);
        $qb3->count('*', 'total');
        $countResult = $qb3->getSingleResult();
        $io->text('Total count: ' . ($countResult['total'] ?? 0));

        $criteria = new class implements CriteriaInterface {
            public function apply(QueryBuilder $qb): void
            {
                $qb->where('status', 'active');
            }
        };
        $repo = $this->entityManager->getRepository(User::class);
        $byCriteria = $repo->findByCriteria($criteria, ['id' => 'ASC'], 3);
        $io->text('By criteria (active, order by id, limit 3): ' . count($byCriteria));

        $chunksProcessed = 0;
        $entitiesProcessed = 0;
        foreach ($this->entityManager->createQueryBuilder(User::class)->orderBy('id')->chunk(2) as $batch) {
            $chunksProcessed++;
            $entitiesProcessed += count($batch);
            $this->entityManager->clear();
        }
        $io->text("Chunk iteration (size=2): {$chunksProcessed} batches, {$entitiesProcessed} entities total");

        return Command::SUCCESS;
    }

    private function seedUsers(): void
    {
        $statuses = ['active', 'pending', 'inactive'];
        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->name = "Query User {$i}";
            $user->email = "query-{$i}-" . uniqid() . '@example.com';
            $user->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
            $user->status = $statuses[$i % 3];
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();
    }
}
