# Performance

Identity map, result cache, query logging, partial hydration, batch iteration.

**Related examples:** [Advanced Querying](../../examples/advanced-querying/README.md)

## Identity Map

EntityManager reuses loaded entities by ID. Same entity loaded twice returns the same instance.

## Result Cache

```php
$qb->enableResultCache($lifetime, $cacheId);
```

Locked queries are never cached.

## Query Logging

Implement `QueryLoggerInterface` for profiling (e.g. `FileQueryLogger`, `PsrQueryLogger`).

## Partial Hydration

Use `PartialHydrator` or `ScalarHydrator` for partial selects when full entity hydration is not needed.

## Batch / Chunk Iteration

`getResult()` loads all rows at once via `fetchAll()`. For large datasets use `chunk()` to process records in fixed-size batches, executing one query per batch:

```php
foreach ($em->createQueryBuilder(Order::class)->orderBy('id')->chunk(500) as $batch) {
    foreach ($batch as $order) {
        $processor->handle($order);
    }
    $em->clear(); // release the batch from the identity map
}
```

Each chunk is a plain array of up to `$size` hydrated entities. The builder's original `limit` and `offset` are restored after iteration.
