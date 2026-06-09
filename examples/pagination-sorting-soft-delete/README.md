# Pagination, Sorting, and Soft Delete

Demonstrates offset pagination, cursor pagination, ordering, and a soft-delete-style
`status = deleted` flag workflow.

**Read details:** [Pagination and Filtering](../../documentation/pagination-filtering/README.md)

## Prerequisites

- Docker services running
- Migrations applied

## Run

```bash
bin/console app:example:pagination-sorting-soft-delete
```

Or via Docker:

```bash
docker compose exec php bin/console app:example:pagination-sorting-soft-delete
```

## Expected Result

Shows offset and cursor pagination, ordering, and soft-delete filter behavior.
