<?php

namespace App\Features\CustomerAccounts\Repository;

use App\Features\CustomerAccounts\Entity\Address;
use App\Features\CustomerAccounts\Entity\Customer;
use App\Features\CustomerAccounts\Entity\CustomerSummary;
use Articulate\Modules\QueryBuilder\CursorPaginator;
use Articulate\Modules\Repository\AbstractRepository;
use Articulate\Modules\Repository\Criteria\BetweenCriteria;
use Articulate\Modules\Repository\Criteria\EqualsCriteria;
use Articulate\Modules\Repository\Criteria\LikeCriteria;

final class CustomerRepository extends AbstractRepository
{
    /**
     * @return Customer[]
     */
    public function findActive(): array
    {
        return $this->findByCriteria(
            new EqualsCriteria('status', 'active'),
            ['id' => 'ASC'],
        );
    }

    /**
     * @return Customer[]
     */
    public function findActiveByEmailDomain(string $domain): array
    {
        return $this->createQueryBuilder()
            ->where('status', 'active')
            ->where('email', 'like', "%@{$domain}")
            ->orderBy('id', 'ASC')
            ->getResult();
    }

    /**
     * @return Customer[]
     */
    public function findByEmailDomain(string $domain): array
    {
        return $this->findByCriteria(
            new LikeCriteria('email', "%@{$domain}"),
            ['id' => 'ASC'],
        );
    }

    /**
     * @return Customer[]
     */
    public function findRegisteredBetween(\DateTime $from, \DateTime $to): array
    {
        return $this->findByCriteria(
            new BetweenCriteria(
                'registered_at',
                $from->format('Y-m-d H:i:s'),
                $to->format('Y-m-d H:i:s'),
            ),
            ['registered_at' => 'ASC', 'id' => 'ASC'],
        );
    }

    public function findWithAddress(int $id): ?Customer
    {
        $customer = $this->find($id);
        if (!$customer instanceof Customer) {
            return null;
        }

        $address = $this->getEntityManager()
            ->createQueryBuilder(Address::class)
            ->where('customer_id', $customer->id)
            ->getSingleResult();

        if ($address instanceof Address) {
            $customer->address = $address;
        }

        return $customer;
    }

    /**
     * @return CustomerSummary[]
     */
    public function findSummariesByEmailDomain(string $domain, int $limit, int $offset = 0): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder(CustomerSummary::class)
            ->where('email', 'like', "%@{$domain}")
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->offset($offset)
            ->getResult(CustomerSummary::class);
    }

    public function findSummaryCursorByEmailDomain(string $domain, ?string $cursor, int $limit): CursorPaginator
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder(CustomerSummary::class)
            ->where('email', 'like', "%@{$domain}")
            ->orderBy('id', 'ASC');

        if ($cursor !== null) {
            $qb->cursor($cursor);
        }

        return $qb
            ->cursorLimit($limit)
            ->getCursorPaginatedResult(CustomerSummary::class);
    }
}
