# Query Builder

Build queries with filters, joins, aggregates, subqueries, and Criteria.

**Runnable example:** [Advanced Querying](../../examples/advanced-querying/README.md)

**Related examples:** [Pagination and Filtering](../pagination-filtering/README.md)

## Basic Usage

```php
$qb = $entityManager->createQueryBuilder(User::class);
$qb->select('*')->from('users')->where('status', 'active')->limit(10);
$results = $qb->getResult();
```

## Where Clauses

- `where($field, $value)` – equality
- `whereIn($field, $values)` – IN
- `whereNull($field)` / `whereNotNull($field)`
- `whereExists($callback)` – subquery
- `whereRaw($sql, ...$params)`

## Joins

- `join($table, $alias, $condition)` – INNER
- `leftJoin`, `rightJoin`, `crossJoin`

## Aggregates

- `count($field, $alias)`, `sum`, `avg`, `max`, `min`

## Criteria

Implement `CriteriaInterface` and apply via `apply($qb)` for reusable filters.

## Chunk Iteration

Process large result sets in batches without loading everything into memory:

```php
foreach ($qb->orderBy('id')->chunk(500) as $batch) {
    foreach ($batch as $entity) {
        // process one entity at a time
    }
}
```

See [Performance](../performance/README.md) for memory management tips.
