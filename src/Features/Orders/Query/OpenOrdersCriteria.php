<?php

namespace App\Features\Orders\Query;

use Articulate\Modules\QueryBuilder\CriteriaInterface;
use Articulate\Modules\QueryBuilder\QueryBuilder;

final class OpenOrdersCriteria implements CriteriaInterface
{
    public function __construct(
        private readonly string $statusColumn = 'status',
    ) {
    }

    public function apply(QueryBuilder $qb): void
    {
        $qb->where($this->statusColumn, 'open');
    }
}
