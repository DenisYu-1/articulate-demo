# Multiple Unit of Work

## What It Demonstrates

Using isolated units of work to keep long-running imports bounded and to avoid letting one failed batch corrupt the primary context.

## Prerequisites

- Docker services running.
- Migrations applied.

## Run Command

```bash
./examples/multiple-unit-of-work/run.sh
```

Equivalent command:

```bash
docker compose exec php bin/console app:example:multiple-unit-of-work
```

Feature-level command:

```bash
docker compose exec php bin/console app:import:run --count=100 --batch-size=20
```

## Expected Output

The example prints separate unit-of-work behavior. The import command prints naive vs scoped import row counts, memory samples, rollback checks, and resumed batches.

## Related Source Files

- [MultipleUnitOfWorkExampleCommand](../../src/Command/Examples/MultipleUnitOfWork/MultipleUnitOfWorkExampleCommand.php)
- [BulkImportRunCommand](../../src/Features/BulkImport/Command/BulkImportRunCommand.php)
- [ImportProduct](../../src/Features/BulkImport/Entity/ImportProduct.php)
- [ImportCategory](../../src/Features/BulkImport/Entity/ImportCategory.php)

## Related Docs

- [Performance](../../documentation/performance/README.md)
- [Bulk Import feature README](../../src/Features/BulkImport/README.md)

## Navigation

Previous: [Transactions and Locking](../transactions-locking/README.md)  
Base: [Examples Index](../README.md)  
Next: [Examples Index](../README.md)
