# Relations

Demonstrates OneToMany, ManyToOne, ManyToMany, and OneToOne relations with loading behavior.

**Read details:** [Relationships](../../documentation/relationships/README.md)

## Prerequisites

- Docker services running
- Migrations applied

## Run

```bash
bin/console app:example:relations
```

Or via Docker:

```bash
docker compose exec php bin/console app:example:relations
```

## Expected Result

Creates User with Phones, Groups, and Cart. Shows relation loading and traversal.
