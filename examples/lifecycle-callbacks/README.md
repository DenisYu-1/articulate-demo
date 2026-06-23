# Lifecycle Callbacks

## What It Demonstrates

Persistence, update, removal, and hydration callbacks around entity state changes.

## Prerequisites

- Docker services running.
- Migrations applied.

## Run Command

```bash
./examples/lifecycle-callbacks/run.sh
```

Equivalent command:

```bash
docker compose exec php bin/console app:example:lifecycle-callbacks
```

Feature-level command:

```bash
docker compose exec php bin/console app:customers:lifecycle
```

## Expected Output

The command prints callback names in the order they fire. The customer command also shows audit-writing and `PostLoad` behavior.

## Related Source Files

- [LifecycleCallbacksExampleCommand](../../src/Command/Examples/LifecycleCallbacksExampleCommand.php)
- [CustomersLifecycleCommand](../../src/Features/CustomerAccounts/Command/CustomersLifecycleCommand.php)
- [Customer](../../src/Features/CustomerAccounts/Entity/Customer.php)

## Related Docs

- [Lifecycle Callbacks](../../documentation/lifecycle-callbacks/README.md)
- [Customer Accounts feature README](../../src/Features/CustomerAccounts/README.md)

## Navigation

Previous: [Pagination, Sorting, Soft Delete](../pagination-sorting-soft-delete/README.md)  
Base: [Examples Index](../README.md)  
Next: [Custom Types](../custom-types/README.md)
