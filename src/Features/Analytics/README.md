# Analytics Feature

## Feature Purpose

Analytics is a read-side feature for reporting over orders, order items, and products. It demonstrates projections, aggregate queries, additive column migrations, result caching, query logging, and bounded-memory batch reads.

## Entities

- `OrderSnapshot` maps the reporting fields from `orders`, including the Analytics-owned `analytics_channel` column.
- `OrderItemSnapshot` maps reporting fields from `order_items`, including the Analytics-owned `margin_amount` column.
- `ProductSnapshot` maps reporting fields from `products`, including the Analytics-owned `analytics_family` column.
- `DateRangeCriteria` and `StatusFilterCriteria` compose reusable report filters.

## Commands

- `app:analytics:report` prints revenue, top products, status counts, and result-cache hit/miss behavior.
- `app:analytics:batch` processes order snapshots in bounded batches and compares memory behavior with and without clearing the `EntityManager`.

## Articulate Concepts Demonstrated

- Projection entities mapped to existing write-model tables.
- Additive migrations that extend existing feature tables with reporting columns.
- Aggregate query builder functions.
- `CriteriaInterface`.
- Result cache and query logging.
- Identity-map memory management during batch reads.

```php
$batch = $this->entityManager
    ->createQueryBuilder(OrderSnapshot::class)
    ->whereIn('id', $orderIds)
    ->orderBy('placed_at', 'ASC')
    ->chunk($chunkSize);

foreach ($batch as $rows) {
    // Process one bounded result set.
}
```

## Related Docs

- [Performance](../../../documentation/performance/README.md)
- [Query Builder](../../../documentation/query-builder/README.md)
- [Known Limitations](../../../documentation/known-limitations/README.md)

## Known Caveats

- Scalar and partial hydration have current edge cases; see [Known Limitations](../../../documentation/known-limitations/README.md).
- Command-level query logging currently uses an instrumented local connection because loggers are accepted at connection construction time.
