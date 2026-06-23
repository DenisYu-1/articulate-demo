# Custom Types

## What It Demonstrates

Conversion between PHP-specific values and database-friendly values.

## Prerequisites

- Docker services running.
- Migrations applied.

## Run Command

```bash
./examples/custom-types/run.sh
```

Equivalent command:

```bash
docker compose exec php bin/console app:example:custom-types
```

Feature-level command:

```bash
docker compose exec php bin/console app:catalog:crud
```

## Expected Output

The example prints converted values. The Catalog command shows product status stored as a string and exposed through enum helper methods.

## Related Source Files

- [CustomTypesExampleCommand](../../src/Command/Examples/CustomTypesExampleCommand.php)
- [ProductStatusConverter](../../src/Features/Catalog/Type/ProductStatusConverter.php)
- [Product](../../src/Features/Catalog/Entity/Product.php)

## Related Docs

- [Custom Types](../../documentation/custom-types/README.md)
- [Catalog feature README](../../src/Features/Catalog/README.md)

## Navigation

Previous: [Lifecycle Callbacks](../lifecycle-callbacks/README.md)  
Base: [Examples Index](../README.md)  
Next: [Transactions and Locking](../transactions-locking/README.md)
