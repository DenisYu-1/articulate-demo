# Basic CRUD

## What It Demonstrates

Minimal entity persistence: create, find, update, and remove. Use this first to verify the application, database, and ORM wiring are working.

## Prerequisites

- Docker services running with `docker compose up -d`.
- Composer dependencies installed in the PHP container.
- Migrations applied with `bin/console articulate:migrate`.

## Run Command

```bash
./examples/basic-crud/run.sh
```

Equivalent command:

```bash
docker compose exec php bin/console app:example:basic-crud
```

## Expected Output

The command creates a user, flushes it, reloads it by id, updates it, removes it, and prints each step.

## Related Source Files

- [BasicCrudExampleCommand](../../src/Command/Examples/BasicCrudExampleCommand.php)
- [User](../../src/Entity/User.php)
- [Catalog feature README](../../src/Features/Catalog/README.md)

## Related Docs

- [Getting Started](../../documentation/getting-started/README.md)
- [Entity Mapping](../../documentation/entity-mapping/README.md)

## Navigation

Previous: [Examples Index](../README.md)  
Base: [Examples Index](../README.md)  
Next: [Migrations Workflow](../migrations-workflow/README.md)
