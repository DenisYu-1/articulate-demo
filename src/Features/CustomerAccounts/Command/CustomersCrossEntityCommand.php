<?php

namespace App\Features\CustomerAccounts\Command;

use App\Features\Analytics\Diagnostics\CountingQueryLogger;
use App\Features\CustomerAccounts\Entity\Address;
use App\Features\CustomerAccounts\Entity\Customer;
use App\Features\CustomerAccounts\Entity\CustomerSummary;
use Articulate\Connection;
use Articulate\Modules\EntityManager\EntityManager;
use Articulate\Modules\QueryBuilder\Filter\SoftDeleteFilter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
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
        $this->demonstrateSecondLevelCacheSiblingEviction($io, $suffix);
        $this->demonstrateStaleRelationReference($io, $suffix);

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

    private function demonstrateSecondLevelCacheSiblingEviction(SymfonyStyle $io, string $suffix): void
    {
        $io->section('Second-level cache sibling eviction');

        $customer = $this->createCustomerWithAddress(
            'L2 Original Customer',
            "l2-cross-{$suffix}@example.test",
            "L2 Cross {$suffix}",
        );
        $customerId = $customer->id;
        $this->entityManager->clear();

        $pool = new ArrayAdapter();
        $readerLogger = new CountingQueryLogger();
        $readerEm = $this->createL2EntityManager($readerLogger, $pool);
        $writerEm = $this->createL2EntityManager(null, $pool);

        $readerLogger->reset();
        $readerEm->find(Customer::class, $customerId);
        $readerEm->find(CustomerSummary::class, $customerId);
        $warmQueries = $readerLogger->count();
        $readerEm->clear();

        $readerLogger->reset();
        $readerEm->find(Customer::class, $customerId);
        $readerSummaryBefore = $readerEm->find(CustomerSummary::class, $customerId);
        $cacheHitQueries = $readerLogger->count();
        $readerEm->clear();

        // Load both sibling classes in the writing context so the metadata
        // registry knows every entity class that maps the customers table.
        $writerCustomer = $writerEm->find(Customer::class, $customerId);
        $writerEm->find(CustomerSummary::class, $customerId);

        if (!$writerCustomer instanceof Customer || !$readerSummaryBefore instanceof CustomerSummary) {
            throw new \RuntimeException('Cannot demonstrate L2 sibling eviction.');
        }

        $writerCustomer->name = 'L2 Updated For All Contexts';
        $writerEm->flush();
        $writerEm->clear();

        $readerLogger->reset();
        $readerCustomerAfter = $readerEm->find(Customer::class, $customerId);
        $readerSummaryAfter = $readerEm->find(CustomerSummary::class, $customerId);
        $afterUpdateQueries = $readerLogger->count();

        $io->definitionList(
            ['warm Customer + CustomerSummary cache entries' => "{$warmQueries} query(s)"],
            ['reader context before update' => "{$cacheHitQueries} query(s), summary='{$readerSummaryBefore->name}'"],
            ['writer context updated Customer.name' => $writerCustomer->name],
            ['reader Customer after writer flush' => $readerCustomerAfter instanceof Customer ? $readerCustomerAfter->name : 'missing'],
            ['reader CustomerSummary after writer flush' => $readerSummaryAfter instanceof CustomerSummary ? $readerSummaryAfter->name : 'missing'],
            ['reader reload queries after writer flush' => "{$afterUpdateQueries} query(s)"],
        );
    }

    private function demonstrateStaleRelationReference(SymfonyStyle $io, string $suffix): void
    {
        $io->section('Stale relation reference');

        $customer = $this->createCustomerWithAddress(
            'Stale Relation Customer',
            "stale-relation-$suffix@example.test",
            "Stale Relation $suffix",
        );
        $customerId = $customer->id;
        $this->entityManager->clear();

        $loadedCustomer = $this->entityManager->find(Customer::class, $customerId);
        if (!$loadedCustomer instanceof Customer) {
            throw new \RuntimeException('Cannot demonstrate stale relation reference.');
        }

        $loadedAddress = $this->entityManager->loadRelation($loadedCustomer, 'address');
        if (!$loadedAddress instanceof Address) {
            throw new \RuntimeException('Customer address relation was not loaded.');
        }

        $addressId = $loadedAddress->id;
        $this->entityManager->getConnection()->executeQuery(
            'UPDATE customer_addresses SET city = ? WHERE id = ?',
            ['London', $addressId],
        );

        $freshAddress = $this->entityManager->find(Address::class, $addressId);
        if (!$freshAddress instanceof Address) {
            throw new \RuntimeException('Updated address was not reloaded.');
        }

        $qbAddress = $this->entityManager
            ->createQueryBuilder(Address::class)
            ->where('id', $addressId)
            ->getSingleResult();

        if (!$qbAddress instanceof Address) {
            throw new \RuntimeException('Address queryBuilder reload failed.');
        }

        $io->definitionList(
            ['old value' => 'Berlin'],
            ['new value' => 'London'],
            ['fresh Address city after refetch' => $freshAddress->city],
            ['queryBuilder Address city after refetch' => $qbAddress->city],
            ['customer->address city after refetch' => $loadedCustomer->address?->city ?? 'missing'],
            ['same in-memory instance retrieved from find: (expected: yes)' => $loadedAddress === $freshAddress ? 'yes' : 'no'],
            ['queryBuilder is fresh (expected: no)' => $qbAddress->city !== $loadedAddress->city ? 'yes' : 'no'],
        );
    }

    private function createL2EntityManager(?CountingQueryLogger $logger, ArrayAdapter $pool): EntityManager
    {
        $connection = new Connection(
            $this->env('DATABASE_DSN'),
            $this->env('DATABASE_USER'),
            $this->env('DATABASE_PASSWORD'),
            $logger,
        );

        $entityManager = new EntityManager($connection, secondLevelCache: $pool);
        $entityManager->getFilters()->add('soft_delete', new SoftDeleteFilter());

        return $entityManager;
    }

    private function env(string $name): string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
        if (!is_string($value) || $value === '') {
            throw new \RuntimeException("Missing required environment variable {$name}.");
        }

        return $value;
    }
}
