# Transactions and Locking

Demonstrates transactional(), lock(), and rollback behavior.

**Read details:** [Transactions and Locking](../../documentation/transactions-locking/README.md)

## Prerequisites

- Docker services running
- Migrations applied

## Run

```bash
bin/console app:example:transactions-locking
```

Or via Docker:

```bash
docker compose exec php bin/console app:example:transactions-locking
```

## Expected Result

Shows successful transaction commit and rollback on error.
