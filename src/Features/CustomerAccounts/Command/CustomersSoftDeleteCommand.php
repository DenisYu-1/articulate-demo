<?php

namespace App\Features\CustomerAccounts\Command;

use App\Features\CustomerAccounts\Entity\Customer;
use Articulate\Modules\EntityManager\EntityManager;
use PDOException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:customers:soft-delete', description: 'Customer soft-delete filter demo')]
final class CustomersSoftDeleteCommand extends Command
{
    use CustomerCommandSupport;

    public function __construct(
        private readonly EntityManager $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $suffix = bin2hex(random_bytes(4));
        $domain = "soft-{$suffix}.test";

        $this->enableSoftDeleteFilter();

        $io->section('Customer soft delete');

        $ids = $this->seedSoftDeleteCustomers($domain);
        $repo = $this->customerRepository();

        $carol = $this->entityManager->find(Customer::class, $ids['carol']);
        if (!$carol instanceof Customer) {
            throw new \RuntimeException('Carol was not seeded.');
        }

        $carol->markDeleted();
        $this->entityManager->persist($carol);
        $this->entityManager->flush();
        $this->entityManager->clear();
        $io->text('Carol soft-deleted via managed update');

        $visibleQb = $this->entityManager
            ->createQueryBuilder(Customer::class)
            ->where('email', 'like', "%@{$domain}")
            ->orderBy('id', 'ASC');
        $io->text('Filtered SQL: ' . $visibleQb->getSQL());
        $io->text('Visible after soft-delete: ' . count($visibleQb->getResult()));

        $deletedByPk = $this->entityManager->find(Customer::class, $ids['carol']);
        $io->text('find(Customer, Carol id): ' . ($deletedByPk instanceof Customer ? 'visible' : 'null'));

        $activeInDomain = $repo->findActiveByEmailDomain($domain);
        $io->text('Repository active finder after soft-delete: ' . count($activeInDomain));

        $allQb = $this->entityManager
            ->createQueryBuilder(Customer::class)
            ->withoutFilter('soft_delete')
            ->where('email', 'like', "%@{$domain}")
            ->orderBy('id', 'ASC');
        $io->text('withoutFilter SQL: ' . $allQb->getSQL());
        $io->text('Rows with filter bypass: ' . count($allQb->getResult()));

        $this->demonstrateUniqueConflict($io, $domain);

        $nextQb = $this->entityManager
            ->createQueryBuilder(Customer::class)
            ->where('email', 'like', "%@{$domain}");
        $io->text('Next query re-applies filter: ' . $nextQb->getSQL());

        $this->reactivateCustomer($ids['carol']);
        $visibleAfterReactivation = $repo->findByEmailDomain($domain);
        $io->text('Visible after reactivation: ' . count($visibleAfterReactivation));

        $io->success('Customer soft-delete demo completed');

        return Command::SUCCESS;
    }

    /**
     * @return array{alice: int, bob: int, carol: int}
     */
    private function seedSoftDeleteCustomers(string $domain): array
    {
        $alice = $this->createCustomerWithAddress('Alice Soft', "alice@{$domain}", 'Alice Soft');
        $this->entityManager->clear();

        $bob = $this->createCustomerWithAddress('Bob Soft', "bob@{$domain}", 'Bob Soft');
        $this->entityManager->clear();

        $carol = $this->createCustomerWithAddress('Carol Soft', "carol@{$domain}", 'Carol Soft');
        $this->entityManager->clear();

        return [
            'alice' => $alice->id,
            'bob' => $bob->id,
            'carol' => $carol->id,
        ];
    }

    private function demonstrateUniqueConflict(SymfonyStyle $io, string $domain): void
    {
        try {
            $duplicate = $this->createCustomer('Carol Duplicate', "carol@{$domain}");
            $this->entityManager->persist($duplicate);
            $this->entityManager->flush();

            $io->error('Expected unique email conflict, but duplicate insert succeeded');
        } catch (PDOException $e) {
            $this->entityManager->clear();
            $io->text('Soft-deleted email still conflicts with unique index: ' . $this->shortError($e));
        }
    }

    private function reactivateCustomer(int $customerId): void
    {
        $carol = $this->entityManager
            ->createQueryBuilder(Customer::class)
            ->withoutFilter('soft_delete')
            ->where('id', $customerId)
            ->getSingleResult();

        if (!$carol instanceof Customer) {
            throw new \RuntimeException('Cannot reactivate missing customer.');
        }

        $carol->reactivate();
        $this->entityManager->persist($carol);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
