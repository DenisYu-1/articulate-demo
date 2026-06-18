# Migrations

Schema diffing and migration generation.

**Runnable example:** [Migrations Workflow](../../examples/migrations-workflow/README.md)

## Commands

- `articulate:init` – create migration tracking table
- `articulate:diff` – generate migrations from entity ↔ DB diff
- `articulate:migrate` – run pending migrations

## Configuration

```yaml
parameters:
    articulate_entities_path: 'src/Entity'
    articulate_migrations_path: 'migrations/%env(database_driver:resolve:DATABASE_DSN)%'
    articulate_migrations_namespace: 'App\Migrations'
```

The active migration directory is derived from the PDO driver in `DATABASE_DSN`. A MySQL DSN uses `migrations/mysql`; a PostgreSQL DSN uses `migrations/pgsql`.

## Workflow (Clean Database)

1. Ensure database is running (e.g. `docker compose up -d`)
2. Run `articulate:init` to create the migrations tracking table
3. Run `articulate:diff` to generate migrations from entity vs DB diff
4. Run `articulate:migrate` to apply pending migrations

From a clean database, the first `articulate:diff` generates migrations for all entity tables. Subsequent runs generate only the delta between current entities and the database schema.
