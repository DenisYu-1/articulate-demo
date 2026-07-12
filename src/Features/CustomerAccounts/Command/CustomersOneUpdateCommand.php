<?php

namespace App\Features\CustomerAccounts\Command;

use App\Features\Analytics\Diagnostics\CountingQueryLogger;
use App\Features\CustomerAccounts\Entity\Customer;
use App\Features\CustomerAccounts\Entity\CustomerSummary;
use Articulate\Exceptions\UpdateConflictException;
use Articulate\Connection;
use Articulate\Modules\EntityManager\EntityManager;
use Articulate\Modules\QueryBuilder\Filter\SoftDeleteFilter;
use Articulate\Modules\EntityManager\ThrowOnUpdateConflictStrategy;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:customers:one-update', description: 'Same-row projection update and conflict strategy demo')]
final class CustomersOneUpdateCommand extends Command
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
        $customer = $this->createCustomerWithAddress(
            'One Update Customer',
            "one-update-seed-{$suffix}@example.test",
            "One Update {$suffix}",
        );

        if ($customer->id === null) {
            throw new \RuntimeException('Seed customer id was not assigned.');
        }

        $customerId = $customer->id;
        $this->demonstrateMergedUpdate($io, $customerId, $suffix);
        $this->demonstrateThrownConflict($io, $customerId, $suffix);

        return Command::SUCCESS;
    }

    private function demonstrateMergedUpdate(SymfonyStyle $io, int $customerId, string $suffix): void
    {
        $observedLogger = new CountingQueryLogger();
        $observedEm = $this->createObservedEntityManager($observedLogger);

        $managedCustomer = $observedEm->find(Customer::class, $customerId);
        $managedSummary = $observedEm->find(CustomerSummary::class, $customerId);

        if (!$managedCustomer instanceof Customer || !$managedSummary instanceof CustomerSummary) {
            throw new \RuntimeException('Observed entities were not loaded.');
        }

        $managedCustomer->status = 'paused';
        $managedCustomer->email = 'update@example.test';
        $managedSummary->email = "one-update-final-{$suffix}@example.test";

        $observedLogger->reset();
        $observedEm->persist($managedCustomer);
        $observedEm->persist($managedSummary);
        $observedEm->flush();

        $customerUpdates = array_values(array_filter(
            $observedLogger->queries(),
            static fn (array $query): bool => str_starts_with(strtoupper($query['sql']), 'UPDATE CUSTOMERS SET')
        ));

        $observedEm->clear();
        $freshCustomer = $observedEm->find(Customer::class, $customerId);
        $freshSummary = $observedEm->find(CustomerSummary::class, $customerId);

        if (!$freshCustomer instanceof Customer || !$freshSummary instanceof CustomerSummary) {
            throw new \RuntimeException('Updated entities were not reloaded.');
        }

        $io->section('One SQL update for two classes');
        $io->definitionList(
            ['updated through Customer' => "status={$managedCustomer->status}"],
            ['updated through CustomerSummary' => "email={$managedSummary->email}"],
            ['customer table UPDATE statements' => (string) count($customerUpdates)],
            ['SQL' => $customerUpdates[0]['sql'] ?? 'missing'],
            ['SQL parameters' => isset($customerUpdates[0]) ? json_encode($customerUpdates[0]['parameters'], JSON_THROW_ON_ERROR) : 'missing'],
            ['reloaded Customer.status' => $freshCustomer->status],
            ['reloaded CustomerSummary.email' => $freshSummary->email],
        );
    }

    private function demonstrateThrownConflict(SymfonyStyle $io, int $customerId, string $suffix): void
    {
        $conflictEm = $this->createObservedEntityManager();
        $conflictEm->setUpdateConflictResolutionStrategy(new ThrowOnUpdateConflictStrategy());

        $managedCustomer = $conflictEm->find(Customer::class, $customerId);
        $managedSummary = $conflictEm->find(CustomerSummary::class, $customerId);

        if (!$managedCustomer instanceof Customer || !$managedSummary instanceof CustomerSummary) {
            throw new \RuntimeException('Conflict entities were not loaded.');
        }

        $managedCustomer->email = "conflict-customer-{$suffix}@example.test";
        $managedSummary->email = "conflict-summary-{$suffix}@example.test";

        $conflictEm->persist($managedCustomer);
        $conflictEm->persist($managedSummary);

        try {
            $conflictEm->flush();
            throw new \RuntimeException('Expected ThrowOnUpdateConflictStrategy to reject the flush.');
        } catch (UpdateConflictException $e) {
            $io->section('ThrowOnUpdateConflictStrategy');
            $io->definitionList(
                ['conflicting column' => 'email'],
                ['Customer email' => $managedCustomer->email],
                ['CustomerSummary email' => $managedSummary->email],
                ['flush result' => 'UpdateConflictException'],
                ['message' => $e->getMessage()],
            );
        }
    }

    private function createObservedEntityManager(?CountingQueryLogger $logger = null): EntityManager
    {
        $connection = new Connection(
            $this->env('DATABASE_DSN'),
            $this->env('DATABASE_USER'),
            $this->env('DATABASE_PASSWORD'),
            $logger,
        );

        $entityManager = new EntityManager($connection);
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
