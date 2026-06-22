<?php

namespace App\Features\CustomerAccounts\Command;

use App\Features\CustomerAccounts\Entity\Customer;
use App\Features\CustomerAccounts\Entity\CustomerSummary;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:customers:cross-entity', description: 'Customer same-table projection demo')]
final class CustomersCrossEntityCommand extends Command
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

        $this->enableSoftDeleteFilter();

        $io->section('Customer cross-entity behavior');

        $customer = $this->createCustomerWithAddress(
            'Cross Entity Customer',
            "cross-{$suffix}@example.test",
            "Cross {$suffix}",
        );
        $customerId = $customer->id;
        $this->entityManager->clear();

        $loadedCustomer = $this->entityManager->find(Customer::class, $customerId);
        $loadedSummary = $this->entityManager->find(CustomerSummary::class, $customerId);
        $sameCustomer = $this->entityManager->find(Customer::class, $customerId);

        if (!$loadedCustomer instanceof Customer || !$loadedSummary instanceof CustomerSummary) {
            throw new \RuntimeException('Cross-entity customer was not seeded.');
        }

        $io->text('Customer and CustomerSummary are same object: ' . ($loadedCustomer === $loadedSummary ? 'yes' : 'no'));
        $io->text('find(Customer) identity-map same instance: ' . ($loadedCustomer === $sameCustomer ? 'yes' : 'no'));

        $loadedCustomer->name = 'Changed Through Customer';
        $this->entityManager->flush();
        $io->text('CustomerSummary in memory after Customer update: ' . $loadedSummary->name);

        $this->entityManager->clear();
        $freshSummary = $this->entityManager->find(CustomerSummary::class, $customerId);
        $io->text('CustomerSummary after clear/refetch: ' . ($freshSummary instanceof CustomerSummary ? $freshSummary->name : 'missing'));

        $this->demonstrateMergedUpdate($io, $customerId);
        $this->demonstrateRemoveEvictsSummary($io, $suffix);
        $this->demonstrateReverseRemoveEvictsCustomer($io, $suffix);

        $summaryRepository = $this->entityManager->getRepository(CustomerSummary::class);
        $io->text('Entity without repositoryClass uses: ' . basename(str_replace('\\', '/', $summaryRepository::class)));

        $io->success('Customer cross-entity demo completed');

        return Command::SUCCESS;
    }

    private function demonstrateMergedUpdate(SymfonyStyle $io, int $customerId): void
    {
        $this->entityManager->clear();

        $customer = $this->entityManager->find(Customer::class, $customerId);
        $summary = $this->entityManager->find(CustomerSummary::class, $customerId);

        if (!$customer instanceof Customer || !$summary instanceof CustomerSummary) {
            throw new \RuntimeException('Cannot demonstrate merged update on missing customer.');
        }

        $customer->name = 'Customer Persist Wins First';
        $summary->name = 'Summary Persist Wins Last';

        $this->entityManager->persist($customer);
        $this->entityManager->persist($summary);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $fresh = $this->entityManager->find(Customer::class, $customerId);
        $io->text('Same-column conflict winner: ' . ($fresh instanceof Customer ? $fresh->name : 'missing'));
    }

    private function demonstrateRemoveEvictsSummary(SymfonyStyle $io, string $suffix): void
    {
        $customer = $this->createCustomerWithAddress(
            'Remove Customer',
            "remove-customer-{$suffix}@example.test",
            "Remove Customer {$suffix}",
        );
        $customerId = $customer->id;
        $this->entityManager->clear();

        $customer = $this->entityManager->find(Customer::class, $customerId);
        $summary = $this->entityManager->find(CustomerSummary::class, $customerId);

        if (!$customer instanceof Customer || !$summary instanceof CustomerSummary) {
            throw new \RuntimeException('Cannot demonstrate Customer removal.');
        }

        $this->entityManager->remove($customer);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $summaryAfterDelete = $this->entityManager->find(CustomerSummary::class, $customerId);
        $io->text('remove(Customer) then find(CustomerSummary): ' . ($summaryAfterDelete instanceof CustomerSummary ? 'visible' : 'null'));
    }

    private function demonstrateReverseRemoveEvictsCustomer(SymfonyStyle $io, string $suffix): void
    {
        $customer = $this->createCustomerWithAddress(
            'Reverse Remove Customer',
            "remove-summary-{$suffix}@example.test",
            "Remove Summary {$suffix}",
        );
        $customerId = $customer->id;
        $this->entityManager->clear();

        $summary = $this->entityManager->find(CustomerSummary::class, $customerId);
        $customer = $this->entityManager->find(Customer::class, $customerId);

        if (!$customer instanceof Customer || !$summary instanceof CustomerSummary) {
            throw new \RuntimeException('Cannot demonstrate CustomerSummary removal.');
        }

        $this->entityManager->remove($summary);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $customerAfterDelete = $this->entityManager->find(Customer::class, $customerId);
        $io->text('remove(CustomerSummary) then find(Customer): ' . ($customerAfterDelete instanceof Customer ? 'visible' : 'null'));
    }
}
