# Advanced Querying

## What It Demonstrates

Query builder filters, `whereIn`, `whereExists`, joins, aggregates, subqueries, and reusable Criteria.

## Prerequisites

- Docker services running.
- Migrations applied.

## Run Command

```bash
./examples/advanced-querying/run.sh
```

Equivalent command:

```bash
docker compose exec php bin/console app:example:advanced-querying
```

## Expected Output

The command seeds sample rows and prints query results for each query pattern. For feature-level order queries, also run `app:orders:query`.

## Related Source Files

- [AdvancedQueryingExampleCommand](../../src/Command/Examples/AdvancedQueryingExampleCommand.php)
- [CatalogQueryCommand](../../src/Features/Catalog/Command/CatalogQueryCommand.php)
- [OrdersQueryCommand](../../src/Features/Orders/Command/OrdersQueryCommand.php)

## Related Docs

- [Query Builder](../../documentation/query-builder/README.md)
- [Known Limitations](../../documentation/known-limitations/README.md)

## Navigation

Previous: [Relations](../relations/README.md)  
Base: [Examples Index](../README.md)  
Next: [Pagination, Sorting, Soft Delete](../pagination-sorting-soft-delete/README.md)
