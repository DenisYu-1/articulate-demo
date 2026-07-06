<?php

namespace App\Features\Analytics\Query;

use Articulate\Modules\QueryBuilder\CriteriaInterface;
use Articulate\Modules\QueryBuilder\QueryBuilder;

final class DateRangeCriteria implements CriteriaInterface
{
    public function __construct(
        private readonly string $column,
        private readonly \DateTimeInterface $from,
        private readonly \DateTimeInterface $to,
    ) {
    }

    public function apply(QueryBuilder $qb): void
    {
        $qb->where($this->column, 'between', [
            $this->from->format('Y-m-d H:i:s'),
            $this->to->format('Y-m-d H:i:s'),
        ]);
    }
}
