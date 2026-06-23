# Transactions and Locking

## What It Demonstrates

Automatic transactions, manual transaction control, rollback, row locks, and deterministic lock ordering.

## Prerequisites

- Docker services running.
- Migrations applied.

## Run Command

```bash
./examples/transactions-locking/run.sh
```

Equivalent command:

```bash
docker compose exec php bin/console app:example:transactions-locking
```

Feature-level commands:

```bash
docker compose exec php bin/console app:orders:place
docker compose exec php bin/console app:orders:deadlock
```

## Expected Output

The example prints transaction and rollback states. The Orders commands show stock row locking, order persistence, transaction-required lock failures, and deterministic lock ordering.

## Related Source Files

- [TransactionsLockingExampleCommand](../../src/Command/Examples/TransactionsLockingExampleCommand.php)
- [OrdersPlaceCommand](../../src/Features/Orders/Command/OrdersPlaceCommand.php)
- [OrdersDeadlockCommand](../../src/Features/Orders/Command/OrdersDeadlockCommand.php)
- [StockLock](../../src/Features/Orders/Entity/StockLock.php)

## Related Docs

- [Transactions and Locking](../../documentation/transactions-locking/README.md)
- [Orders feature README](../../src/Features/Orders/README.md)

## Navigation

Previous: [Custom Types](../custom-types/README.md)  
Base: [Examples Index](../README.md)  
Next: [Multiple Unit of Work](../multiple-unit-of-work/README.md)
