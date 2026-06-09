# Multiple Unit of Work

Demonstrates using one `EntityManager` and a secondary `UnitOfWork` to keep an anchor entity in memory while processing many related entities in an isolated identity scope.

## Problem

When creating many entities in a loop (e.g. 10,000 posts for one user), a single Unit of Work identity map accumulates every entity you touch. Without clearing, memory grows unbounded. If you clear that same Unit of Work, you lose the user reference you need for the next iteration.

## Solution

Use one `EntityManager` with a secondary `UnitOfWork`:

- **Primary EM** — holds the User across all iterations. Never cleared.
- **Secondary UnitOfWork** — creates each Post, then `flush()` and `clear()` on that UnitOfWork each iteration.

The secondary UnitOfWork still uses the same `EntityManager` and the same `Connection`; it just isolates identities and clears frequently.

## Highlights

- **`flush()` + `clear()`** — persist changes and clear the secondary UnitOfWork identity map
- **Identity map isolation** — each EntityManager has its own; clearing one does not affect the other
- **Memory control** — when each iteration creates a large entity graph, clearing the secondary EM keeps memory bounded

## When to Use

Batch importers, data pipelines, report generators — any scenario where you stream a large dataset and write derived entities in chunks while keeping a small set of anchor entities in memory.

## Prerequisites

- Docker services running
- Migrations applied

## Run

```bash
bin/console app:example:multiple-unit-of-work
```

Or via Docker:

```bash
docker compose exec php bin/console app:example:multiple-unit-of-work
```

## Expected Result

Creates one user and 10 posts. Each post is flushed and the secondary UnitOfWork is cleared before the next. The user remains in the primary UnitOfWork’s identity map throughout.
