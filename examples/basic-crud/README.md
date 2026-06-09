# Basic CRUD

Demonstrates entity mapping, persist, find, update, and remove.

**Read details:** [Entity Mapping](../../documentation/entity-mapping/README.md)

## Prerequisites

- Docker services running (`docker compose up -d`)
- Migrations applied (`bin/console articulate:migrate`)

## Run

```bash
bin/console app:example:basic-crud
```

Or via Docker:

```bash
docker compose exec php bin/console app:example:basic-crud
```

## Expected Result

Creates a User, persists it, finds by ID, updates, and removes. Output shows each step.
