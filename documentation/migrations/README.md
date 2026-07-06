# Migrations

Generate and apply schema changes from Articulate entity metadata.

**Runnable commands:** `articulate:init`, `articulate:migrate`, `articulate:diff`

## What This Covers

- Initializing the migration tracking table
- Generating diffs from entity metadata and the live database
- Applying pending migrations
- Switching between MySQL and PostgreSQL migration folders

## Commands

- `articulate:init` creates the migration tracking table.
- `articulate:diff` generates migrations from the entity/database schema diff.
- `articulate:migrate` runs pending migrations.

## Configuration

```yaml
parameters:
    articulate_entities_path: 'src'
    articulate_migrations_path: '%env(resolve:ARTICULATE_MIGRATIONS_PATH)%'
    articulate_migrations_namespace: 'App\Migrations'
```

The demo keeps driver-specific migrations in `migrations/mysql` and `migrations/pgsql`. Set `ARTICULATE_MIGRATIONS_PATH` to the active folder.

## Clean Database Workflow

1. Start services with `docker compose up -d`.
2. Run `articulate:init`.
3. Run `articulate:migrate`.

The demo ships with migrations under `migrations/mysql`, so `articulate:diff` is not required from a clean checkout. Run `articulate:diff` when you change entity metadata and want Articulate to generate new migration files from the schema difference.

From a clean database, the first diff can generate migrations for all mapped entity tables. Later diffs should only contain the delta.

## Transactional Migrations

Migrations run inside a transaction by default. Override `isTransactional()` when a migration contains database operations that must run outside a transaction, such as PostgreSQL `CREATE INDEX CONCURRENTLY`.

```php
protected function isTransactional(): bool
{
    return false;
}
```

## Common Pitfalls

- Confirm `ARTICULATE_MIGRATIONS_PATH` points at the driver you are using before running diff or migrate.
- Do not commit generated diffs without reviewing them against the intended entity change.
- PostgreSQL concurrent index creation requires a non-transactional migration.
- Polymorphic pivot schemas currently have comparison caveats; see [Known Limitations](../known-limitations/README.md).

## Navigation

Previous: [Entity Mapping](../entity-mapping/README.md)  
Base: [Documentation Index](../README.md)  
Next: [Relationships](../relationships/README.md)
