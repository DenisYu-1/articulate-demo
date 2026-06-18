<?php

namespace App\Feature\CustomerAccounts\Command;

use App\Feature\CustomerAccounts\Entity\Address;
use App\Feature\CustomerAccounts\Entity\Customer;
use App\Feature\CustomerAccounts\Repository\CustomerRepository;
use Articulate\Modules\QueryBuilder\Filter\SoftDeleteFilter;

trait CustomerCommandSupport
{
    private function enableSoftDeleteFilter(): void
    {
        $this->entityManager->getFilters()->add('soft_delete', new SoftDeleteFilter());
    }

    private function installAuditWriter(): void
    {
        Customer::setAuditWriter(function (Customer $customer): void {
            $this->entityManager->getConnection()->executeQuery(
                'INSERT INTO customer_audit_entries (customer_id, action, message, created_at) VALUES (?, ?, ?, ?)',
                [
                    $customer->id,
                    'welcome',
                    "Welcome audit entry for {$customer->email}",
                    self::now(),
                ],
            );
        });
    }

    private function customerRepository(): CustomerRepository
    {
        $repository = $this->entityManager->getRepository(Customer::class);
        if (!$repository instanceof CustomerRepository) {
            throw new \RuntimeException('Customer repository was not resolved from entity metadata.');
        }

        return $repository;
    }

    private function createAddress(string $label, ?int $customerId = null): Address
    {
        $address = new Address();
        $address->street = "{$label} Market Street";
        $address->city = 'Berlin';
        $address->state = null;
        $address->postal_code = '10115';
        $address->country = 'Germany';
        $address->customer_id = $customerId;

        return $address;
    }

    private function createCustomer(string $name, string $email, ?int $addressId = null, string $status = 'active'): Customer
    {
        $customer = new Customer();
        $customer->name = $name;
        $customer->email = $email;
        $customer->status = $status;
        $customer->address_id = $addressId;

        return $customer;
    }

    private function createCustomerWithAddress(string $name, string $email, string $addressLabel): Customer
    {
        $address = $this->createAddress($addressLabel);
        $this->entityManager->persist($address);
        $this->entityManager->flush();

        $customer = $this->createCustomer($name, $email, $address->id);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        $this->linkAddressToCustomer($address, $customer);

        return $customer;
    }

    private function linkAddressToCustomer(Address $address, Customer $customer): void
    {
        $this->entityManager->getConnection()->executeQuery(
            'UPDATE customer_addresses SET customer_id = ? WHERE id = ?',
            [$customer->id, $address->id],
        );

        $address->customer_id = $customer->id;
    }

    private function setRegisteredAt(Customer $customer, string $registeredAt): void
    {
        $this->entityManager->getConnection()->executeQuery(
            'UPDATE customers SET registered_at = ? WHERE id = ?',
            [$registeredAt, $customer->id],
        );

        $customer->registered_at = $registeredAt;
    }

    private function countAuditEntries(int $customerId): int
    {
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(*) as total')
            ->from('customer_audit_entries')
            ->where('customer_id', $customerId)
            ->getResult();

        return (int) ($rows[0]['total'] ?? 0);
    }

    private function callbacks(object $entity): string
    {
        $callbacks = property_exists($entity, 'callbacksCalled') ? $entity->callbacksCalled : [];

        return $callbacks === [] ? '(none)' : implode(' -> ', $callbacks);
    }

    private function shortError(\Throwable $e): string
    {
        $message = preg_replace('/\s+/', ' ', $e->getMessage());

        return mb_strimwidth($message ?? '', 0, 180, '...');
    }

    private static function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }
}
