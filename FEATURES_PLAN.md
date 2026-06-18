# Features Plan

Domain: **e-commerce shop** (products, customers, orders). Realistic enough to motivate every ORM pattern without being contrived.

Each feature lives in `src/Feature/<Name>/` and owns its entities, commands, and any supporting classes. Entities map to shared DB tables — articulate supports multiple entity classes on the same table, so features declare only the columns they need.

---

## Feature: Catalog — DONE

**Status:** Implemented in `src/Feature/Catalog/`; schema is managed by migration `Migration20260616000100Catalog`.

**Domain:** Products, categories, and inventory.

**Entities:**
- `Product` — demonstrates all `#[Property]` options: `name`, `nullable`, `maxLength`, custom column name via `name:`. Has `#[Index]` (unique SKU, composite on `category_id`+`status`).
- `Category` — simple parent entity; `ManyToMany` with `Product`.
- `InventorySlot` — maps `product_stock` table; holds `product_id` + `stock` quantity. Target of pessimistic lock in Orders.
- `ProductStatus` — PHP-backed enum; converted by a custom `ProductStatusConverter`.

**Commands:**
- `app:catalog:crud` — persist categories and products, demonstrate CRUD, load categories through the many-to-many pivot, and show required-column + duplicate-SKU failures
- `app:catalog:query` — basic queries: `find`, `findBy`, `where`, `whereIn`, `orderBy`, offset pagination

**Library features demonstrated:**
- Entity mapping: `#[Entity]`, `#[PrimaryKey]`, `#[AutoIncrement]`, `#[Property]` (all options), `#[Index]` (unique + composite)
- Custom type converters: `ProductStatusConverter` maps enum ↔ DB string for product status helper methods
- `ManyToMany` relation (Product ↔ Category)
- Basic query builder: selects, filters, ordering, offset pagination

**Edge cases:**
- Persist `Product` without required `slug` value → ORM attempts INSERT → database raises a NOT NULL/default constraint error; show driver-specific message for MySQL and PostgreSQL
- Persist `Product` with duplicate SKU → DB unique constraint violation bubbles up as `PDOException`

---

## Feature: CustomerAccounts

**Status:** Implemented in `src/Feature/CustomerAccounts/`; schema is managed by migration `Migration20260616000200CustomerAccounts`.

**Implementation notes:** Current Articulate `remove()` schedules a physical delete, so the soft-delete command demonstrates logical deletion via managed `deleted_at` update while still showing `PreRemove`/`PostRemove` on a disposable physical-delete row. Lazy relation proxies also cannot be flushed safely in this release, so the address relation demo uses explicit/eager relation loading. Snake_case entity fields intentionally keep explicit `#[Property(name: ...)]` mappings until the hydrator fallback fix is available in this demo dependency.

**Pending library recheck:** After the not-yet-merged library fixes land in this demo dependency, re-run this module without the current workarounds and verify snake_case hydration/change tracking, lazy relation proxies, `#[SoftDeleteable] remove()` semantics, and `#[PreUpdate]` on implicit dirty flush.

**Domain:** Customer registration, profile, soft-deletable accounts.

**Entities:**
- `Customer` — `#[Entity(tableName: 'customers', repositoryClass: CustomerRepository::class)]`; has `#[PrePersist]` to stamp `registered_at`, `#[PostPersist]` to write a welcome audit entry, `#[PreUpdate]` to stamp `updated_at`, `#[PreRemove]` for soft-delete logic
- `CustomerSummary` — **second class mapping the same `customers` table** with only `id`, `name`, `email`; used for lightweight list queries
- `Address` — `OneToOne` owned by customer

**Repository:**
- `CustomerRepository extends AbstractRepository` — wired via `repositoryClass:` on `#[Entity]`; exposes domain-specific finders:
  - `findActive(): array` — wraps `EqualsCriteria('status', 'active')`
  - `findByEmailDomain(string $domain): array` — uses `LikeCriteria('email', "%@{$domain}")`
  - `findRegisteredBetween(\DateTime $from, \DateTime $to): array` — uses `BetweenCriteria('registered_at', ...)`
  - `findWithAddress(int $id): ?Customer` — `find()` + `loadRelation()`
- Retrieved via `$em->getRepository(Customer::class)` — `RepositoryFactory` resolves the class from entity metadata and caches the instance

**Commands:**
- `app:customers:lifecycle` — register, update, soft-delete; print each lifecycle callback as it fires including `#[PostLoad]` (shown on `find()`, `getResult()`, and explicit relation load)
- `app:customers:browse` — paginated customer list using `CustomerSummary` (offset) and cursor pagination; `loadRelation()` demo; all queries routed through `CustomerRepository`
- `app:customers:soft-delete` — dedicated command for soft-delete filter behavior (see soft-delete demonstration below)
- `app:customers:cross-entity` — dedicated command for same-table multi-class behavior (see edge cases below)

**Library features demonstrated:**
- Lifecycle callbacks: full sequence `PrePersist → PostPersist → PreUpdate → PostUpdate → PreRemove → PostRemove → PostLoad`
- Multiple entity classes on same table (partial mapping)
- `OneToOne` relation
- Soft-delete filter — auto-exclusion, bypass via `$qb->withoutFilter('soft_delete')`, `find()` by PK on deleted row
- Cursor pagination vs offset pagination (same query, two approaches)
- `loadRelation($entity, 'address')` — explicit relation loading
- Custom repository: `#[Entity(repositoryClass:)]`, `AbstractRepository`, built-in criteria (`EqualsCriteria`, `LikeCriteria`, `BetweenCriteria`, `AndCriteria`)
- `$em->getRepository(EntityClass)` — factory resolves and caches repository instance

**Soft-delete filter demonstration (`app:customers:soft-delete`):**
1. Seed three customers: Alice (active), Bob (active), Carol (active)
2. `remove($carol)` + `flush()` → `#[PreRemove]` sets `deleted_at = NOW()`; no DELETE fires; confirm with query log
3. `$repo->findAll()` → returns Alice + Bob only — filter appends `WHERE deleted_at IS NULL` automatically; show generated SQL
4. `find(Customer::class, $carolId)` → **null** — `find()` by PK also passes through the filter; soft-deleted row is invisible
5. `$qb->withoutFilter('soft_delete')->getResult()` → returns all three; show generated SQL without the filter clause
6. `findActive()` via `CustomerRepository` (wraps `EqualsCriteria('status', 'active')`) → still excludes Carol even without explicit filter call — filter is global; show that criteria compose with it
7. Re-activate Carol: load via `withoutFilter`, set `deleted_at = null`, flush → Carol reappears in `findAll()` without filter bypass

**Edge cases:**
- Throw inside `#[PrePersist]` — entity never persisted, transaction rolls back cleanly
- Soft-deleted row invisible to `find()` by PK (not just list queries) — common surprise
- `withoutFilter` scope — applies per query builder instance only; the next `$em->createQueryBuilder()` call re-applies all global filters
- `getRepository()` on an entity with no `repositoryClass:` → returns generic `EntityRepository`
- **Identity map same-instance guarantee** — `find(Customer::class, 1)` called twice returns the exact same PHP object reference; mutation via one variable is immediately visible via the other without any DB round-trip
- **Flush without persist on a managed entity** — load a customer (becomes MANAGED), modify a property, call `flush()` without `persist()`; `DeferredImplicitStrategy` computes change sets for ALL managed entities on flush — the change IS detected and UPDATE fires; re-persisting is not required for already-managed entities
- **`#[PostLoad]` fires on all hydration paths** — confirmed: `ObjectHydrator.hydrate()` invokes postLoad callbacks; this covers `find()`, `getResult()`, and relation lazy-loading
- **Circular relation** — `Customer → Address → Customer` back-reference; identity map short-circuits re-hydration rather than recursing infinitely
- **Soft delete + unique index** — soft-delete a customer, create a new one with the same email; DB unique index blocks it even though the old record is logically deleted; show the conflict and discuss workarounds (composite unique index including `deleted_at`)
- **Cursor pagination requires unique sort key** — order by a non-unique column (e.g. `registered_at`); cursor becomes unstable when multiple rows share the sort value; demonstrate adding `id` as tiebreaker

**Cross-entity class behavior (`app:customers:cross-entity`):**
- `find(Customer::class, 1)` then `find(CustomerSummary::class, 1)` → **two DB queries** — identity map keyed by `class + id`; each class is an independent context regardless of what's in memory
- `Customer.name` updated and flushed → `CustomerSummary.name` in memory stays stale — **intentional** (bounded-context isolation; snapshot tracking means CustomerSummary only writes columns it explicitly modified)
- Update flush → **L2 cache evicted for all entity classes sharing that table+pk** — next `find(CustomerSummary::class, 1)` goes to DB and returns the fresh row
- `persist(customer)` + `persist(summary)` on same row in one flush → `MergeUpdateConflictResolutionStrategy` groups by `table|pk`, merges SET clauses → **one UPDATE fires**
- Same-column conflict (both explicitly modify `name`) → last `persist()` wins; no error — demonstrate by showing which value survives
- `remove($customer)` + `flush()` → DELETE fires → **both `Customer` and `CustomerSummary` evicted from identity map and L2 cache**; subsequent `find(CustomerSummary::class, 1)` → null
- Demonstrate reverse: `remove($summary)` + `flush()` → Customer also evicted

| Scenario | Identity map | L2 cache |
|----------|-------------|----------|
| Remove   | Evict all sibling classes (ghost-read fix) | Evict all sibling classes |
| Update   | No propagation (bounded-context by design) | Evict all sibling classes |

---

## Feature: Orders

**Status:** Implemented in `src/Feature/Orders/`; schema is managed by migration `Migration20260616000300Orders`.

**Implementation notes:** Current Articulate UUID generation happens in `QueryExecutor::executeInsert()`, so the demo schedules the order insert while the primary key is still null, then assigns a UUID before `flush()` to keep the insert scheduled and still show a pre-flush UUID. `where('shipped_at', null)` currently compiles as `= ?` with a null parameter, so `app:orders:query` prints that SQL and uses `whereNull()` for the working null comparison. The deadlock command demonstrates transaction-required locking, rollback, and deterministic lock ordering without creating a real concurrent database deadlock.

**Domain:** Order placement and inventory locking.

**Entities:**
- `Order` — `#[PrimaryKey]` with UUID generator (ID available before flush, unlike AutoIncrement); `ManyToOne` to customer via `customer_id`; `OneToMany` to `OrderItem`; `#[PrePersist]` for `placedAt`
- `OrderItem` — `ManyToOne` to `Order`; stores `product_id`, `quantity`, `unitPrice`
- `StockLock` — **second class mapping `product_stock` table** (same table as Catalog's `InventorySlot`); declares only `product_id` + `stock` — used exclusively for locking and decrement during order placement

**Commands:**
- `app:orders:place` — place an order inside `transactional()`; lock `StockLock` row with `SELECT ... FOR UPDATE`, decrement stock, persist order; show UUID available on `$order->id` before flush
- `app:orders:query` — complex queries: `INNER JOIN` orders+items, aggregate `SUM(quantity * unit_price)` per order, `whereExists` (customers with at least one open order), reusable `OpenOrdersCriteria`; also covers query builder edge cases below
- `app:orders:deadlock` — show the two opposite lock orders that would deadlock under concurrency; demonstrate transaction-required locks, manual transaction commit, rollback after failure, and the fix: acquire locks in deterministic product_id order or retry the transaction

**Library features demonstrated:**
- `transactional()` wrapper — auto commit/rollback
- Manual `beginTransaction` / `commit` / `rollback`
- `$qb->lock()` — `SELECT ... FOR UPDATE`
- `OneToMany` + `ManyToOne`
- Complex query builder: `join`, `leftJoin`, `sum`/`count` aggregates, `whereExists` with subquery, `CriteriaInterface`
- Manual batch loading — load orders, then contrast per-order `loadRelation()` calls with one `OrderItem` batch query
- UUID generator — ID set before flush via the schedule-then-assign workaround; contrast with AutoIncrement where `$entity->id` is null until after flush
- **Query builder edge cases** (covered in `app:orders:query`):
  - `whereIn('id', [])` — empty array; show generated SQL and result (empty collection, no error)
  - `where('shipped_at', null)` — current release generates `= ?` with a null parameter; contrast with working explicit `whereNull()`
  - `whereRaw` — correct parameterized form (`whereRaw('total > ?', [100])`) vs concatenated string (injection risk)

**Edge cases:**
- `lock()` called outside transaction → `TransactionRequiredException`
- Exception mid-`transactional()` — stock not decremented, order not persisted
- **Deadlock during concurrent stock locking** — transaction A locks product 1 then waits on product 2 while transaction B locks product 2 then waits on product 1; command prints the conflicting lock order and demonstrates mitigation via consistent lock ordering plus rollback behavior
- **N+1 lazy load vs batch load** — load orders, call `loadRelation()` per order, then show one `OrderItem` batch query
- **FK insert ordering** — ORM performs topological sort on flush; inserts execute parents before children regardless of persist() call order; FK constraint never fires

---

## Feature: Tagging

**Status:** Implemented in `src/Feature/Tagging/`; schema is managed by migration `Migration20260616000400Tagging`.

**Implementation notes:** Current Articulate metadata supports `MorphToMany` / `MorphedByMany`, but `EntityManager::loadRelation()` returns `null` for those relation objects in this dependency, so the runnable command keeps the attributes and queries the `taggables` pivot directly. The demo sets `targetIdColumn: 'tag_id'` explicitly because the default would be `tags_id`. The migration stores `taggable_id` as `VARCHAR(36)` because the same polymorphic column must hold order UUIDs and customer integer ids; current schema comparison hardcodes this column as `int`, so recheck after ORM fixes.

**Domain:** Labels that can be attached to any taggable entity — Orders and Customers in this demo.

**Entities:**
- `Tag` — maps `tags` table; has `#[MorphedByMany(targetEntity: TaggableOrder::class, name: 'taggable')]` and `#[MorphedByMany(targetEntity: TaggableCustomer::class, name: 'taggable')]`
- `TaggableOrder` — **projection mapping `orders` table** with only `id` + `#[MorphToMany(targetEntity: Tag::class, name: 'taggable')]`; pivot table `taggables` (columns: `tag_id`, `taggable_type`, `taggable_id`)
- `TaggableCustomer` — same pattern, projection of `customers` table with only `id`
- Pivot table `taggables` is schema-only; no entity maps it directly

**Commands:**
- `app:tagging:demo` — create tags, attach to orders and customers via pivot, load tags from each side, load all orders for a given tag via inverse relation, show current ORM `loadRelation()` gap

**Library features demonstrated:**
- `#[MorphToMany]` — owner side (TaggableOrder/TaggableCustomer → Tag)
- `#[MorphedByMany]` — inverse side (Tag → TaggableOrder/TaggableCustomer)
- `MorphTypeRegistry::register(TaggableOrder::class, 'order')` — short alias stored in `taggable_type` instead of full FQCN
- Pivot table convention: `{name}s` → `taggables`
- Polymorphic load from Tag side: "give me all orders tagged 'urgent'"

**Edge cases:**
- Query tags on an entity with no tags — empty collection, not null
- Unregistered morph alias — `taggable_type` stores FQCN fallback; show alias vs no-alias difference in DB

---

## Feature: Analytics

**Domain:** Reporting over orders and products. Uses read-side projection entities — lighter classes mapping the same tables with only the columns needed for reporting.

**Entities:**
- `OrderSnapshot` — maps `orders` table; fields: `id`, `status`, `placed_at`. Read-only projection; no relations.
- `OrderItemSnapshot` — maps `order_items` table; fields: `order_id`, `product_id`, `quantity`, `unit_price`.
- `ProductSnapshot` — maps `products` table; fields: `id`, `name`, `category_id`. For joining in reports.

**Commands:**
- `app:analytics:report` — revenue by category, top products, order counts per status; result cache on expensive aggregates; run twice to show cache hit vs query log difference
- `app:analytics:batch` — iterate all `OrderSnapshot` records via `chunk(500)` + `$em->clear()`; print `memory_get_usage()` before/after to show bounded vs unbounded growth

**Library features demonstrated:**
- `$qb->enableResultCache($ttl, $key)` — cache hit vs miss via QueryLogger output
- `chunk($size)` — memory-bounded batch iteration
- `CriteriaInterface` — `DateRangeCriteria` + `StatusFilterCriteria` composed on one builder
- Aggregate functions: `count`, `sum`, `avg`, `max`
- `ScalarHydrator` / `PartialHydrator` — projection queries without full entity hydration
- Multiple entity classes on same table (projection pattern)
- `QueryLoggerInterface` — log queries to output; show cache hit produces zero queries

**Edge cases:**
- `chunk()` without `$em->clear()` — identity map grows unbounded; measure and compare
- Stale result cache — update a record, re-query within TTL, observe stale result
- **L2 cache scope is `find()` by PK only** — `findBy()`, `getResult()`, `chunk()` always hit DB regardless of L2 cache state; warm L2 cache does not help list queries

---

## Feature: BulkImport

**Domain:** Importing a large product catalog from an external source (simulated with generated data).

**Entities:**
- `ImportProduct` — maps `products` table; declares all columns needed for import (`sku`, `name`, `category_id`, `status`, `price`). Different class than Catalog's `Product` — demonstrates two feature-local entity classes writing the same table.
- `ImportCategory` — maps `categories` table; used as anchor entity held in the primary `EntityManager` across batches.

**Commands:**
- `app:import:run` — imports N products; first naive run (single UoW, 5k entities, watch memory); second scoped run (primary EM holds `ImportCategory` anchors, secondary UoW per batch of 500, `flush()` + `clear()` per iteration, flat memory)

**Library features demonstrated:**
- Scoped / secondary `UnitOfWork` — secondary UoW handles each batch
- Primary EM holds anchor entities across batch boundaries
- Per-batch `flush()` + `clear()` on secondary UoW
- Identity map isolation between primary and secondary UoW

**Edge cases:**
- Naive single-UoW: show memory growth curve
- Batch failure midway — primary EM unaffected, failed batch rolled back, import resumes from next batch

---

## Summary Table

| Feature          | Entities                                                      | Key library features                                                          |
|------------------|---------------------------------------------------------------|-------------------------------------------------------------------------------|
| Catalog          | Product, Category, InventorySlot, ProductStatus               | mapping attrs, indexes, custom types, basic queries                           |
| CustomerAccounts | Customer, CustomerSummary, Address                            | lifecycle, partial mapping, soft-delete, cursor pag, custom repo, cross-entity |
| Orders           | Order (UUID), OrderItem, StockLock                            | transactions, locking, eager loading, complex queries, criteria, UUID gen     |
| Tagging          | Tag, TaggableOrder, TaggableCustomer                          | MorphToMany, MorphedByMany, MorphTypeRegistry                                 |
| Analytics        | OrderSnapshot, OrderItemSnapshot, ProductSnapshot             | result cache, query logger, chunk, aggregates, hydrators, L2 scope            |
| BulkImport       | ImportProduct, ImportCategory                                 | scoped unit of work, memory-bounded batching                                  |

---

## What is NOT covered (and why)

- **`MorphTo` / `MorphOne` / `MorphMany`** — single-record polymorphic variants; covered enough by the MorphToMany demo
- **DQL / JPQL** — not supported by library; out of scope
- **Read replicas** — no built-in read/write routing by design; infrastructure concern (PgBouncer, ProxySQL, RDS Proxy); pattern is to instantiate a second `EntityManager` with a replica `Connection` — worth a note in docs but not a runnable demo without a replica DB service

## Migrations edge cases

Separate from feature commands — demonstrated via the `articulate:*` CLI directly:

- **Adding NOT NULL column to table with existing rows** — `articulate:diff` generates the migration correctly, but applying it fails if the table has data and no default is specified; fix: add a default in the migration SQL before dropping it to NOT NULL (two-step migration)
- **`articulate:validate`** — already covered by `app:example:incomplete-entity`

## PostgreSQL

Full support — runtime queries and schema management. `SchemaReaderFactory` picks `MySqlSchemaReader` or `PostgresqlSchemaReader` based on driver; `PostgresqlSchemaReader` uses `information_schema` queries, no MySQL-specific syntax.

Demo runs against both MySQL and PostgreSQL. Add a second docker compose service plus `app:pg:smoke` / `app:mysql:smoke` commands that run the same CRUD/query operations against each driver, proving parity instead of only stating it.
