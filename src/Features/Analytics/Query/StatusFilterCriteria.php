<?php

namespace App\Features\Analytics\Query;

use Articulate\Modules\QueryBuilder\CriteriaInterface;
use Articulate\Modules\QueryBuilder\QueryBuilder;

final class StatusFilterCriteria implements CriteriaInterface
{
    /**
     * @param string[] $statuses
     */
    public function __construct(
        private readonly string $column,
        private readonly array $statuses,
    ) {
    }

    public function apply(QueryBuilder $qb): void
    {
        if ($this->statuses === []) {
            return;
        }

        $qb->whereIn($this->column, $this->statuses);
    }
}
