# Analytics Feature

## Feature Purpose

Analytics is a read-side feature for reporting over orders, order items, and products. It demonstrates projections, aggregate queries, result caching, query logging, and bounded-memory batch reads.

## Entities

- `OrderSnapshot` maps the reporting fields from `orders`.
- `OrderItemSnapshot` maps reporting fields from `order_items`.
- `ProductSnapshot` maps reporting fields from `products`.
- `DateRangeCriteria` and `StatusFilterCriteria` compose reusable report filters.

## Commands

- `app:analytics:report` prints revenue, top products, status counts, and result-cache hit/miss behavior.
- `app:analytics:batch` processes order snapshots in bounded batches and compares memory behavior with and without clearing the `EntityManager`.

## Articulate Concepts Demonstrated

- Projection entities mapped to existing write-model tables.
- Aggregate query builder functions.
- `CriteriaInterface`.
- Result cache and query logging.
- Identity-map memory management during batch reads.

```php
$batch = $this->entityManager
    ->createQueryBuilder(OrderSnapshot::class)
    ->whereIn('id', $orderIds)
    ->orderBy('placed_at', 'ASC')
    ->limit($chunkSize)
    ->offset($offset)
    ->getResult();
```

## Related Docs and Examples

- [Performance](../../../documentation/performance/README.md)
- [Query Builder](../../../documentation/query-builder/README.md)
- [Known Limitations](../../../documentation/known-limitations/README.md)
- [Advanced Querying](../../../examples/advanced-querying/README.md)
- [Multiple Unit of Work](../../../examples/multiple-unit-of-work/README.md)

## Known Caveats

- `QueryBuilder::chunk()` is documented by the library but unavailable in the installed dependency, so `app:analytics:batch` uses a limit/offset fallback.
- Scalar and partial hydration have current edge cases; see [Known Limitations](../../../documentation/known-limitations/README.md).
- Command-level query logging currently uses an instrumented local connection because loggers are accepted at connection construction time.
