# Articulate ORM Demo

Demo and documentation project for the [Articulate](https://github.com/denisyu-1/articulate) ORM library — a lightweight, attribute-driven PHP ORM for MySQL/PostgreSQL.

## What Is Articulate?

Articulate maps plain PHP classes to database tables using PHP 8 attributes. No base classes, no XML, no YAML — only attributes on your entity properties.

**Key features:**
- Attribute-based entity and relation mapping
- Unit of Work pattern with identity map
- Query builder with joins, aggregates, subqueries, and reusable Criteria
- Transactions with `SELECT ... FOR UPDATE` locking
- Offset and cursor pagination
- Lifecycle callbacks (PrePersist, PostPersist, PreUpdate, PostUpdate, PreRemove, PostRemove, PostLoad)
- Custom PHP↔DB type converters
- Schema diff and migration generation

## Later To Be Checked

- `later-to-be-checked`: UUID primary key generation currently happens during insert/flush, not during `persist()`. Orders demo assigns UUID after scheduling the insert and before `flush()` so the row is still inserted and the ID is available pre-flush.
- `later-to-be-checked`: First `persist()` of an entity with an explicit ID marks it managed without scheduling an INSERT. Library side should distinguish new explicit-ID entities from loaded managed entities.
- `later-to-be-checked`: `where('column', null)` compiles to `column = ?` with a null parameter. Use `whereNull()` until null comparisons compile to `IS NULL`.
- `later-to-be-checked`: `QueryBuilder::chunk()` is referenced by docs/examples but is missing in the installed Articulate dependency.
- `later-to-be-checked`: `EntityManager::transactional()` has no retry/max retry arguments, while `Connection::transactional()` does. Orders deadlock demo uses deterministic lock order and rollback checks instead of a real retrying concurrent deadlock fixture.
- `later-to-be-checked`: Relation eager loading is still explicit/manual in demo code. Orders query command shows N+1-style `loadRelation()` calls and a manual batch query workaround.
- `later-to-be-checked`: Relation-owned FK columns must not also be mapped as scalar `#[Property]` fields on the same entity. `articulate:validate` reports duplicate relation/scalar mappings explicitly.
- `later-to-be-checked`: `EntityManager::loadRelation()` returns `null` for `MorphToMany` / `MorphedByMany` metadata because `RelationshipLoader` only handles `ReflectionRelation` after the many-to-many branch. Tagging demo keeps morph attributes but uses direct pivot queries.
- `later-to-be-checked`: `MorphToMany` default target join column for `Tag` resolves to `tags_id`; demo sets `targetIdColumn: 'tag_id'` to match the documented pivot shape.
- `later-to-be-checked`: `compareMorphToManyTable()` hardcodes polymorphic owner id columns as `int`; Tagging needs `VARCHAR(36)` because `taggable_id` stores both order UUIDs and customer integer ids.

## Why Articulate Instead of Doctrine?

Doctrine ORM is mature and full-featured, but carries significant weight: generated proxies, complex metadata drivers, a second-level cache layer, and a steep learning curve for teams new to ORM patterns.

Articulate is built for teams that want the core ORM benefits — identity map, Unit of Work, relation loading, migrations — without the ceremony. There are no proxy classes to generate, no annotation vs. attribute vs. XML driver decisions to make, and no `EntityManagerInterface` indirection. You wire two services, annotate your classes, and run. The tradeoff is explicit: Articulate covers the 90% case well and intentionally omits features like eager-loading configuration, a second-level cache, and DQL. If you need those, Doctrine is the right tool.

## Installation

```bash
composer require denisyu-1/articulate
```

Register two services in your DI container:

```yaml
Articulate\Connection:
    arguments:
        $dsn: '%env(DATABASE_DSN)%'
        $user: '%env(DATABASE_USER)%'
        $password: '%env(DATABASE_PASSWORD)%'

Articulate\Modules\EntityManager\EntityManager:
    arguments:
        $connection: '@Articulate\Connection'
```

## Configuration Reference

**Environment variables**

| Variable | Example | Description |
|----------|---------|-------------|
| `DATABASE_DSN` | `mysql:host=mysql;dbname=mydb;charset=utf8mb4` | PDO DSN |
| `DATABASE_USER` | `user` | Database username |
| `DATABASE_PASSWORD` | `secret` | Database password |
| `DATABASE_NAME` | `mydb` | Database name (referenced inside DSN) |
| `ARTICULATE_MIGRATIONS_PATH` | `migrations/mysql` | Directory where migration files are read and written |
| `APP_ENV` | `dev` / `test` | Symfony environment; test env uses `.env.test` overrides |

**services.yaml parameters** (migration commands)

| Parameter | Default | Description |
|-----------|---------|-------------|
| `articulate_entities_path` | `src/Entity` | Directory scanned for `#[Entity]` classes |
| `articulate_migrations_path` | `%env(resolve:ARTICULATE_MIGRATIONS_PATH)%` | Directory where migration files are read and written |
| `articulate_migrations_namespace` | `App\Migrations` | PHP namespace for generated migration classes |

## Quick Start

```bash
docker compose up -d
docker compose exec php bin/console articulate:init
docker compose exec php bin/console articulate:diff
docker compose exec php bin/console articulate:migrate
docker compose exec php bin/console app:example:basic-crud
```

## Feature Guide

The demo is organized by feature under `src/Features/<Name>/`. Each feature owns its entities, commands, and supporting classes while sharing database tables where that is useful.

| Feature | Where to look | Run | What it demonstrates |
|---------|---------------|-----|----------------------|
| Catalog | `src/Features/Catalog/`, `Migration20260616000100Catalog` | `app:catalog:crud`, `app:catalog:query` | property mapping, indexes, custom enum conversion, many-to-many products/categories, basic queries |
| CustomerAccounts | `src/Features/CustomerAccounts/`, `Migration20260616000200CustomerAccounts` | `app:customers:lifecycle`, `app:customers:browse`, `app:customers:soft-delete`, `app:customers:cross-entity` | lifecycle callbacks, repositories, soft-delete behavior, cursor pagination, same-table entity projections |
| Orders | `src/Features/Orders/`, `Migration20260616000300Orders` | `app:orders:place`, `app:orders:query`, `app:orders:deadlock` | transactions, pessimistic locks, UUID primary keys, order/item relations, complex query builder usage |
| Tagging | `src/Features/Tagging/`, `Migration20260616000400Tagging` | `app:tagging:demo` | polymorphic many-to-many tags, morph aliases, pivot-table queries |
| Analytics | `src/Features/Analytics/` | `app:analytics:report`, `app:analytics:batch` | read-only projections, aggregates, result cache, query logging, batch iteration |
| BulkImport | `src/Features/BulkImport/` | `app:import:run` | write-side projections, scoped units of work, bounded-memory imports |

---

## Library Documentation

### Entity Mapping

Annotate any plain PHP class with `#[Entity]`. No base class required.

```php
#[Entity(tableName: 'users')]
#[Index(['email'], unique: true, concurrent: true)]
#[Index(['created_at', 'status'])]
class User
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(maxLength: 120)]
    public string $name;

    #[Property(name: 'created_at', nullable: true)]
    public ?string $createdAt = null;
}
```

- `#[Entity]` — default table name is the pluralized class name; override with `tableName:`
- `#[Property(name, type, nullable, maxLength)]` — column mapping
- `#[Index([columns], unique, concurrent)]` — concurrent indexes avoid table locks
- Multiple entity classes can map to the same table (e.g. `User` and `LoginUser` for different read contexts)

→ [Entity Mapping docs](documentation/entity-mapping/README.md)

### Relations

```php
#[OneToMany(ownedBy: 'user', targetEntity: Phone::class)]
public array|Collection $phones = [];

#[ManyToMany(targetEntity: Group::class, referencedBy: 'users')]
public array|Collection $groups = [];

#[OneToOne(targetEntity: Cart::class, referencedBy: 'user')]
public ?Cart $cart = null;
```

Available: `#[OneToOne]`, `#[OneToMany]`, `#[ManyToOne]`, `#[ManyToMany]`, and polymorphic variants (`MorphTo`, `MorphOne`, `MorphMany`, `MorphToMany`, `MorphedByMany`).

Relations load automatically during hydration. Use `loadRelation($entity, $relationName)` to load explicitly.

Relation-owned FK columns must be mapped by the relation only. Do not also map the same column as a scalar `#[Property]` on that entity:

```php
// Invalid: both properties map customer_id on the same entity.
#[Property(name: 'customer_id')]
public ?int $customerId = null;

#[ManyToOne(targetEntity: Customer::class, column: 'customer_id')]
public ?Customer $customer = null;
```

Use either relation access or scalar FK access for a column, not both. `articulate:validate` should reject duplicate relation/scalar mappings.

→ [Relationships docs](documentation/relationships/README.md)

### Query Builder

```php
$users = $entityManager->createQueryBuilder(User::class)
    ->select('*')
    ->where('status', 'active')
    ->whereIn('role', ['admin', 'editor'])
    ->whereNotNull('email_verified_at')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->offset(20)
    ->getResult();
```

**Where clauses:** `where`, `whereIn`, `whereNull`, `whereNotNull`, `whereExists` (subquery), `whereRaw`

**Joins:** `join`, `leftJoin`, `rightJoin`, `crossJoin`

**Aggregates:** `count`, `sum`, `avg`, `max`, `min`

**Reusable filters:** implement `CriteriaInterface` and apply with `$qb->apply($criteria)`.

**Batch iteration** (large datasets, memory-safe):

```php
foreach ($qb->orderBy('id')->chunk(500) as $batch) {
    foreach ($batch as $entity) { /* process */ }
    $entityManager->clear();
}
```

→ [Query Builder docs](documentation/query-builder/README.md)

### Transactions and Locking

```php
// Automatic transaction — commits on return, rolls back on exception
$entityManager->transactional(function (EntityManager $em) use ($order) {
    $em->persist($order);
    $em->flush();
    return $order;
});

// Manual control
$entityManager->beginTransaction();
try {
    $entityManager->persist($entity);
    $entityManager->commit(); // flush + commit
} catch (\Throwable $e) {
    $entityManager->rollback();
}

// SELECT ... FOR UPDATE (requires active transaction)
$qb->where('id', $id)->lock()->getResult();
```

→ [Transactions & Locking docs](documentation/transactions-locking/README.md)

### Pagination

```php
// Offset
$qb->limit(10)->offset(20)->getResult();

// Cursor (stable for real-time data)
$result = $qb->cursor($cursor)->cursorLimit(10)->orderBy('id', 'ASC')->getCursorPaginatedResult();

// Soft-delete filter
$qb->withoutFilter('soft_delete'); // include soft-deleted records
```

→ [Pagination & Filtering docs](documentation/pagination-filtering/README.md)

### Lifecycle Callbacks

```php
#[Entity]
class AuditLog
{
    #[PrePersist]
    public function stamp(): void
    {
        $this->createdAt = new \DateTime();
    }

    #[PostLoad]
    public function normalize(): void { /* ... */ }
}
```

Available hooks: `#[PrePersist]`, `#[PostPersist]`, `#[PreUpdate]`, `#[PostUpdate]`, `#[PreRemove]`, `#[PostRemove]`, `#[PostLoad]`.

→ [Lifecycle Callbacks docs](documentation/lifecycle-callbacks/README.md)

### Custom Types

```php
class PointTypeConverter implements TypeConverterInterface
{
    public function convertToPHP(mixed $value): Point { /* parse DB string */ }
    public function convertToDB(mixed $value): string { /* serialize */ }
}

$typeRegistry->registerType('point', new PointTypeConverter());
$typeRegistry->registerClassMapping(Point::class, 'point');
```

Built-in: `BoolTypeConverter`, `DateTimeTypeConverter`, `PointTypeConverter`.

→ [Custom Types docs](documentation/custom-types/README.md)

### Migrations

```bash
bin/console articulate:init    # create migration tracking table (run once)
bin/console articulate:diff    # diff entities vs DB, generate migration file
bin/console articulate:migrate # apply pending migrations
```

Configure paths:

```yaml
parameters:
    articulate_entities_path: 'src/Entity'
    articulate_migrations_path: '%env(resolve:ARTICULATE_MIGRATIONS_PATH)%'
    articulate_migrations_namespace: 'App\Migrations'
```

The demo stores migrations under `migrations/mysql` and `migrations/pgsql`. Set `ARTICULATE_MIGRATIONS_PATH` to the folder that matches `DATABASE_DSN`; when switching database engines, change the environment variables and restart the containers before running the standard migration commands.

The first `articulate:diff` on a clean database generates migrations for all entity tables. Subsequent runs generate only the delta.

→ [Migrations docs](documentation/migrations/README.md)

### Performance

| Feature | How |
|---------|-----|
| Identity map | EM returns same instance for same PK — no duplicate queries |
| Result cache | `$qb->enableResultCache($lifetime, $cacheId)` |
| Partial hydration | `PartialHydrator` / `ScalarHydrator` for partial selects |
| Batch iteration | `chunk($size)` — one query per batch, bounded memory |
| Query logging | Implement `QueryLoggerInterface` (`FileQueryLogger`, `PsrQueryLogger`) |

→ [Performance docs](documentation/performance/README.md)

---

## Real-World Example

The following shows how the features compose in a single entity graph — an `Order` with line items, a status type converter, and lifecycle timestamps. This is the pattern you'd use in a real application.

```php
// Custom type: convert 'pending'|'shipped'|'cancelled' string to an enum
class OrderStatusConverter implements TypeConverterInterface
{
    public function convertToPHP(mixed $value): OrderStatus
    {
        return OrderStatus::from($value);
    }

    public function convertToDB(mixed $value): string
    {
        return $value->value;
    }
}

// Entities
#[Entity(tableName: 'orders')]
#[Index(['customer_id', 'status'])]
class Order
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(name: 'customer_id')]
    public int $customerId;

    #[Property]
    public OrderStatus $status;         // transparently converted by OrderStatusConverter

    #[Property(name: 'created_at')]
    public string $createdAt;

    #[Property(name: 'updated_at', nullable: true)]
    public ?string $updatedAt = null;

    #[OneToMany(ownedBy: 'order', targetEntity: OrderItem::class)]
    public array|Collection $items = [];

    #[PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
        $this->status    = OrderStatus::Pending;
    }

    #[PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = (new \DateTime())->format('Y-m-d H:i:s');
    }
}

#[Entity(tableName: 'order_items')]
class OrderItem
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(name: 'order_id')]
    public int $orderId;

    #[Property(name: 'product_id')]
    public int $productId;

    #[Property]
    public int $quantity;

    #[Property(name: 'unit_price')]
    public string $unitPrice;
}

// Usage
$order = new Order();
$order->customerId = 42;

$item = new OrderItem();
$item->productId = 7;
$item->quantity  = 3;
$item->unitPrice = '29.99';
$order->items[]  = $item;

$entityManager->transactional(function (EntityManager $em) use ($order) {
    $em->persist($order);  // PrePersist stamps createdAt + sets status
    $em->flush();
});

// Later — paginated order history for customer 42
$orders = $entityManager->createQueryBuilder(Order::class)
    ->where('customer_id', 42)
    ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::Shipped->value])
    ->orderBy('created_at', 'DESC')
    ->limit(20)
    ->offset(0)
    ->getResult();
```

---

## Use Cases

### Basic CRUD

Create, load by ID, update a field, and delete in one flow. The identity map ensures `find()` after `persist()`+`flush()` returns the same instance without a round-trip.

```bash
bin/console app:example:basic-crud
```

→ [Source](src/Command/Examples/BasicCrudExampleCommand.php) · [Details](examples/basic-crud/README.md)

---

### Complex Relations (e-commerce model)

One user owns many `Phone` records, belongs to many `Group` records (ManyToMany via pivot), and has one `Cart` (OneToOne). Demonstrates relation traversal and loading behavior.

```bash
bin/console app:example:relations
```

→ [Source](src/Command/Examples/RelationsExampleCommand.php) · [Details](examples/relations/README.md)

---

### Advanced Querying

Filters with `whereIn`, `whereExists` subqueries, `INNER JOIN`, aggregate functions, and reusable `CriteriaInterface` objects for shared filter logic.

```bash
bin/console app:example:advanced-querying
```

→ [Source](src/Command/Examples/AdvancedQueryingExampleCommand.php) · [Details](examples/advanced-querying/README.md)

---

### Transactions and Pessimistic Locking

`transactional()` wrapper for automatic commit/rollback. Manual `beginTransaction`/`commit`/`rollback` for fine-grained control. `SELECT ... FOR UPDATE` to lock rows before write.

```bash
bin/console app:example:transactions-locking
```

→ [Source](src/Command/Examples/TransactionsLockingExampleCommand.php) · [Details](examples/transactions-locking/README.md)

---

### Paginated API Responses

Offset pagination for page-based UIs. Cursor pagination for stable real-time feeds. Multi-column ordering. Soft-delete filter to hide logically deleted records by default.

```bash
bin/console app:example:pagination-sorting-soft-delete
```

→ [Source](src/Command/Examples/PaginationSortingSoftDeleteExampleCommand.php) · [Details](examples/pagination-sorting-soft-delete/README.md)

---

### Audit Logging with Lifecycle Callbacks

`#[PrePersist]` to stamp `created_at`, `#[PostPersist]` to write an audit record, `#[PreUpdate]` for validation. Demonstrates callback execution order across the full entity lifecycle.

```bash
bin/console app:example:lifecycle-callbacks
```

→ [Source](src/Command/Examples/LifecycleCallbacksExampleCommand.php) · [Details](examples/lifecycle-callbacks/README.md)

---

### Custom Type Converters

Map a `Point` struct or `\DateTime` value to a database column type without any casting in entity code. The `TypeRegistry` handles conversion transparently on read and write.

```bash
bin/console app:example:custom-types
```

→ [Source](src/Command/Examples/CustomTypesExampleCommand.php) · [Details](examples/custom-types/README.md)

---

### Batch Import with Multiple Units of Work

**Problem:** importing 10,000 records in a loop — a single Unit of Work accumulates all entities and grows unbounded. Calling `clear()` loses anchor entities (e.g. the parent User) needed for the next batch.

**Solution:** primary `EntityManager` holds anchor entities; secondary `UnitOfWork` handles each batch, then `flush()` + `clear()` per iteration.

```bash
bin/console app:example:multiple-unit-of-work
```

→ [Source](src/Command/Examples/MultipleUnitOfWork/MultipleUnitOfWorkExampleCommand.php) · [Details](examples/multiple-unit-of-work/README.md)

---

### Schema Migrations Workflow

End-to-end migration lifecycle: `articulate:init` creates the tracking table, `articulate:diff` compares entity attributes against the live schema and writes a migration class, `articulate:migrate` runs pending migrations in order.

```bash
bin/console articulate:init
bin/console articulate:diff
bin/console articulate:migrate
```

→ [Details](examples/migrations-workflow/README.md)

---

## Limitations

- **Schema migrations support MySQL and PostgreSQL.** `SchemaReaderFactory` selects `MySqlSchemaReader` or `PostgresqlSchemaReader` based on the active driver; each uses the appropriate introspection queries.
- **Eager loading is supported.** Use `with('relation')` on the query builder to load relations in a single batched query rather than N+1 lazy loads.
- **No second-level cache.** The identity map avoids repeat queries within one `EntityManager` lifetime but does not survive across requests. Result cache (`enableResultCache`) operates at the query-result level only.
- **No DQL or JPQL.** Queries are built via the fluent query builder or raw SQL. There is no object-level query language.
- **Single primary key per entity.** Composite PKs are not supported.

---

## Contributing and Local Dev

The demo project requires PHP 8.4+ and a running MySQL instance (Docker recommended).

```bash
# Start services
docker compose up -d

# Install dependencies
composer install

# Apply schema
docker compose exec php bin/console articulate:init
docker compose exec php bin/console articulate:diff
docker compose exec php bin/console articulate:migrate

# Run tests
composer test

# Run a single test file
php vendor/bin/phpunit tests/path/to/SomeTest.php

# Run with coverage (generates HTML report)
composer test:coverage
```

The library itself lives in `vendor/denis/articulate` (source: [denisyu-1/articulate](https://github.com/denisyu-1/articulate)). To develop against a local checkout, point the `repositories` key in `composer.json` to your local path and run `composer update denis/articulate`.

---

## Requirements

- PHP 8.4+
- MySQL or PostgreSQL
- Docker (for running examples)
