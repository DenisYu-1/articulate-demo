# Lifecycle Callbacks

Demonstrates PrePersist, PostPersist, PreUpdate, PostUpdate, PreRemove, PostRemove, PostLoad.

**Read details:** [Lifecycle Callbacks](../../documentation/lifecycle-callbacks/README.md)

## Prerequisites

- Docker services running
- Migrations applied

## Run

```bash
bin/console app:example:lifecycle-callbacks
```

Or via Docker:

```bash
docker compose exec php bin/console app:example:lifecycle-callbacks
```

## Expected Result

Shows callback execution order during persist, update, and remove.
