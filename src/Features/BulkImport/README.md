# Bulk Import Feature

## Feature Purpose

Bulk Import simulates loading a large product catalog. It contrasts a naive single-unit-of-work import with a scoped batching approach that keeps memory bounded and isolates failed batches.

## Entities

- `ImportProduct` maps the `products` columns required for import.
- `ImportCategory` maps `categories` as the anchor category held by the primary `EntityManager`.

## Commands

- `app:import:run` imports generated products twice: once naively and once with scoped batches. Use `--count` and `--batch-size` for quick smoke runs.

## Articulate Concepts Demonstrated

- Secondary/scoped `UnitOfWork`.
- Primary entity manager retaining anchor entities across batches.
- Per-batch `flush()` and `clear()`.
- Identity-map isolation and rollback behavior.

```bash
bin/console app:import:run --count=100 --batch-size=20
```

## Related Docs and Examples

- [Performance](../../../documentation/performance/README.md)
- [Multiple Unit of Work](../../../examples/multiple-unit-of-work/README.md)
- [Known Limitations](../../../documentation/known-limitations/README.md)

## Known Caveats

- `ImportProduct` maps and fills `slug` because the shared `products` table requires it.
- Default counts are intentionally large for visible memory differences; use smaller options in tests and smoke checks.
