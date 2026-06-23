# Pagination, Sorting, Soft Delete

## What It Demonstrates

Offset pagination, cursor pagination, stable ordering, and soft-delete filtering.

## Prerequisites

- Docker services running.
- Migrations applied.

## Run Command

```bash
./examples/pagination-sorting-soft-delete/run.sh
```

Equivalent command:

```bash
docker compose exec php bin/console app:example:pagination-sorting-soft-delete
```

Feature-level alternatives:

```bash
docker compose exec php bin/console app:customers:browse
docker compose exec php bin/console app:customers:soft-delete
```

## Expected Output

The command prints paged result sets and soft-delete behavior. The customer commands add repository queries, cursor pagination, filter bypass, and reactivation.

## Related Source Files

- [PaginationSortingSoftDeleteExampleCommand](../../src/Command/Examples/PaginationSortingSoftDeleteExampleCommand.php)
- [CustomersBrowseCommand](../../src/Features/CustomerAccounts/Command/CustomersBrowseCommand.php)
- [CustomersSoftDeleteCommand](../../src/Features/CustomerAccounts/Command/CustomersSoftDeleteCommand.php)
- [Customer](../../src/Features/CustomerAccounts/Entity/Customer.php)

## Related Docs

- [Pagination and Filtering](../../documentation/pagination-filtering/README.md)
- [Customer Accounts feature README](../../src/Features/CustomerAccounts/README.md)

## Navigation

Previous: [Advanced Querying](../advanced-querying/README.md)  
Base: [Examples Index](../README.md)  
Next: [Lifecycle Callbacks](../lifecycle-callbacks/README.md)
