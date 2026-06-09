# Migrations Workflow

Demonstrates articulate:init, articulate:diff, and articulate:migrate end-to-end.

**Read details:** [Migrations](../../documentation/migrations/README.md)

## Prerequisites

- Docker services running
- Clean or existing database

## Run

```bash
bin/console articulate:init
bin/console articulate:diff
bin/console articulate:migrate
```

Or via Docker:

```bash
docker compose exec php bin/console articulate:init
docker compose exec php bin/console articulate:diff
docker compose exec php bin/console articulate:migrate
```

## Expected Result

Creates migration tracking table, generates migrations from entity diff, and applies them.
