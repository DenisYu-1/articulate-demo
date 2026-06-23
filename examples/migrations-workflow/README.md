# Migrations Workflow

## What It Demonstrates

The migration lifecycle: initialize migration tracking, generate diffs when entity metadata changes, and apply checked-in migrations.

## Prerequisites

- Docker services running.
- Database configured through the demo environment variables.

## Run Command

```bash
./examples/migrations-workflow/run.sh
```

Equivalent command:

```bash
docker compose exec php bin/console app:example:migrations-workflow
```

For a clean checkout, the real migration command is:

```bash
docker compose exec php bin/console articulate:migrate
```

## Expected Output

The example prints the migration workflow. `articulate:migrate` applies the checked-in migration files under the configured migrations path.

## Related Source Files

- [MigrationsWorkflowExampleCommand](../../src/Command/Examples/MigrationsWorkflowExampleCommand.php)
- [MySQL migrations](../../migrations/mysql)
- [Services configuration](../../config/services.yaml)

## Related Docs

- [Migrations](../../documentation/migrations/README.md)
- [Known Limitations](../../documentation/known-limitations/README.md)

## Navigation

Previous: [Basic CRUD](../basic-crud/README.md)  
Base: [Examples Index](../README.md)  
Next: [Relations](../relations/README.md)
