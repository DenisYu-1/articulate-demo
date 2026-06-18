<?php

namespace App\Feature\CustomerAccounts\Command;

use App\Feature\CustomerAccounts\Entity\Address;
use App\Feature\CustomerAccounts\Entity\Customer;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:customers:lifecycle', description: 'Customer lifecycle callback demo')]
final class CustomersLifecycleCommand extends Command
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
        $this->installAuditWriter();

        try {
            $io->section('Customer lifecycle callbacks');

            $address = $this->createAddress("Lifecycle {$suffix}");
            $this->entityManager->persist($address);
            $this->entityManager->flush();

            $customer = $this->createCustomer(
                name: 'Lifecycle Customer',
                email: "lifecycle-{$suffix}@example.test",
                addressId: $address->id,
            );

            $this->entityManager->persist($customer);
            $io->text('After persist(): ' . $this->callbacks($customer));

            $this->entityManager->flush();
            $this->linkAddressToCustomer($address, $customer);
            $io->text('After insert flush(): ' . $this->callbacks($customer));
            $io->text(sprintf('Welcome audit entries: %d', $this->countAuditEntries($customer->id)));

            $customer->name = 'Lifecycle Customer Updated';
            $this->entityManager->persist($customer);
            $this->entityManager->flush();
            $io->text('After update flush(): ' . $this->callbacks($customer));

            $customerId = $customer->id;
            $this->entityManager->clear();

            $found = $this->entityManager->find(Customer::class, $customerId);
            $io->text('PostLoad via find(): ' . ($found instanceof Customer ? $this->callbacks($found) : 'not found'));

            $this->entityManager->clear();

            $fromQuery = $this->entityManager
                ->createQueryBuilder(Customer::class)
                ->where('id', $customerId)
                ->getSingleResult();
            $io->text('PostLoad via getResult(): ' . ($fromQuery instanceof Customer ? $this->callbacks($fromQuery) : 'not found'));

            if ($fromQuery instanceof Customer) {
                $loadedAddress = $this->entityManager->loadRelation($fromQuery, 'address');
                $io->text('PostLoad via explicit relation load: ' . ($loadedAddress instanceof Address ? $this->callbacks($loadedAddress) : 'no address'));

                $fromQuery->markDeleted();
                $this->entityManager->persist($fromQuery);
                $this->entityManager->flush();
                $io->text('Soft-delete persisted with managed update: ' . $this->callbacks($fromQuery));
            }

            $disposable = $this->createCustomerWithAddress(
                'Disposable Customer',
                "disposable-{$suffix}@example.test",
                "Disposable {$suffix}",
            );
            $this->entityManager->clear();

            $toRemove = $this->entityManager->find(Customer::class, $disposable->id);
            if ($toRemove instanceof Customer) {
                $this->entityManager->remove($toRemove);
                $io->text('After remove(): ' . $this->callbacks($toRemove));
                $this->entityManager->flush();
                $io->text('After delete flush(): ' . $this->callbacks($toRemove));
            }

            $this->demonstratePrePersistRollback($io, $suffix);

            $io->success('Customer lifecycle demo completed');

            return Command::SUCCESS;
        } finally {
            Customer::setAuditWriter(null);
        }
    }

    private function demonstratePrePersistRollback(SymfonyStyle $io, string $suffix): void
    {
        try {
            $this->entityManager->transactional(function (EntityManager $em) use ($suffix): void {
                $rejected = $this->createCustomer(
                    name: 'Rejected Customer',
                    email: "reject-{$suffix}@example.test",
                    status: 'reject-pre-persist',
                );

                $em->persist($rejected);
                $em->flush();
            });

            $io->error('Expected PrePersist rejection, but customer was persisted');
        } catch (\RuntimeException $e) {
            $this->entityManager->clear();
            $io->text('PrePersist exception rolled back cleanly: ' . $this->shortError($e));
        }
    }
}
