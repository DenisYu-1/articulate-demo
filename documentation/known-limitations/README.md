# Known Limitations

This page tracks current demo workarounds and library behavior that should be rechecked as Articulate evolves. Beginner guides link here when a limitation affects the command they describe.

## Mapping

- Snake_case fields in Customer Accounts keep explicit `#[Property(name: ...)]` mappings until hydrator fallback behavior is rechecked.
- Same-table projections are supported, but each entity class is still an independent identity-map context. `Customer` and `CustomerSummary` for the same row are different PHP objects.
- Projection entities still need primary-key metadata for clean hydration, identity-map registration, `find()`, and second-level cache behavior.

## Relations

- Lazy relation proxies cannot currently be flushed safely in the installed dependency, so demos prefer explicit `loadRelation()` when a relation needs to be shown.
- `EntityManager::loadRelation()` currently returns `null` for `MorphToMany` and `MorphedByMany` relation objects, so the Tagging command queries the pivot table directly.
- Relation-owned foreign key columns should not also be mapped as scalar properties on the same entity.

## Query Builder

- `where('column', null)` currently compiles as `column = ?` with a null parameter. Use `whereNull('column')` or `whereNotNull('column')`.
- `QueryBuilder::chunk()` is documented by the library but unavailable in the installed dependency, so batch demos use limit/offset loops.
- `whereRaw()` should use bound parameters, for example `whereRaw('total > ?', [100])`; concatenating user input is unsafe.

## Migrations

- The demo includes checked-in migrations, so a clean checkout can run `articulate:migrate` without first running `articulate:diff`.
- `articulate:diff` is useful when changing entity metadata, but it may expose current schema-comparison gaps around polymorphic pivot columns.
- The Tagging pivot stores `taggable_id` as `VARCHAR(36)` because it must hold both integer customer ids and UUID order ids. The current checked-in schema also has a required technical `id` column even though the natural pivot key is `taggable_type`, `taggable_id`, and `tag_id`.

## Hydration

- Normal aggregate or specific-column query builder selects force raw array hydration before custom hydrators can run.
- `ScalarHydrator` currently returns scalar values that can still be sent to Unit of Work registration, causing type errors in some paths.
- `PartialHydrator` delegates through object hydration in a way that can temporarily register an empty-id entity before partial fields are applied.

## Caching

- Second-level cache serves `find()` by class and primary key. It does not serve `findBy()`, query-builder `getResult()`, or chunked/list reads.
- Writes evict sibling classes that share the same table and primary key, but the in-memory identity map does not synchronize different projection objects already loaded in the same manager.
- Result cache can return stale aggregate data inside its TTL.

## Navigation

Previous: [Performance](../performance/README.md)  
Base: [Documentation Index](../README.md)  
Next: [Repository README](../../README.md)
