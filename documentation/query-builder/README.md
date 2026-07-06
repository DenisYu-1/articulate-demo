# Query Builder

Build database queries with filters, joins, aggregates, subqueries, and reusable Criteria.

**Runnable feature commands:** `app:catalog:query`, `app:orders:query`, `app:analytics:report`  
**Related guide:** [Pagination and Filtering](../pagination-filtering/README.md)

## What This Covers

- Fluent query construction
- Where clauses and raw predicates
- Joins and aggregate functions
- Subqueries
- Reusable Criteria objects
- Chunk-style batch reads when available

## Basic Usage

```php
$qb = $entityManager->createQueryBuilder(User::class);

$users = $qb
    ->select('*')
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->getResult();
```

## Where Clauses

- `where($field, $value)` for equality.
- `whereIn($field, $values)` for `IN (...)`.
- `whereNull($field)` and `whereNotNull($field)` for null checks.
- `whereExists($callback)` for subqueries.
- `whereRaw($sql, ...$params)` for escape-hatch SQL with bound parameters.

## Joins and Aggregates

Use `join`, `leftJoin`, `rightJoin`, and `crossJoin` for table joins.

Use `count`, `sum`, `avg`, `max`, and `min` for aggregate selects.

## Criteria

Implement `CriteriaInterface` when a filter should be reusable across repositories or commands, then apply it to a builder.

```php
$orders = $this->entityManager
    ->createQueryBuilder(Order::class)
    ->whereNull('shipped_at')
    ->orderBy('placed_at', 'DESC')
    ->getResult();
```

Use `whereRaw()` only with bound parameters:

```php
$qb->whereRaw('total > ?', [100]);
```

## Batch Reads

For large result sets, prefer bounded batches and clear the `EntityManager` between batches so the identity map does not grow without limit. See [Performance](../performance/README.md) for memory behavior.

## Common Pitfalls

- Use `whereNull()` for SQL null checks. `where('column', null)` is a current limitation listed in [Known Limitations](../known-limitations/README.md).
- Empty `whereIn()` input should be treated intentionally. The Orders demo prints the generated SQL and empty-result behavior.
- Avoid concatenating user input into `whereRaw()`.

## Navigation

Previous: [Relationships](../relationships/README.md)  
Base: [Documentation Index](../README.md)  
Next: [Pagination and Filtering](../pagination-filtering/README.md)
