# Custom Types

Demonstrates TypeConverterInterface and custom type registration (e.g. Point, JSON).

**Read details:** [Custom Types](../../documentation/custom-types/README.md)

## Prerequisites

- Docker services running
- Migrations applied (including custom type columns if any)

## Run

```bash
bin/console app:example:custom-types
```

Or via Docker:

```bash
docker compose exec php bin/console app:example:custom-types
```

## Expected Result

Shows custom type conversion between PHP and database.
