# Orders Feature

## Feature Purpose

Orders connects customers, order items, inventory locking, transactions, and higher-level query builder examples. It shows how a write flow coordinates several mapped tables in one unit of work.

## Entities

- `Order` maps `orders`, uses a UUID primary key, belongs to a customer, and owns order items.
- `OrderItem` maps `order_items` and stores product, quantity, and unit price.
- `StockLock` maps `product_stock` as a lock-focused projection over the Catalog inventory table.

## Commands

- `app:orders:place` places an order inside `transactional()`, locks stock rows, decrements inventory, and persists order data.
- `app:orders:query` demonstrates joins, aggregates, subqueries, reusable Criteria, null handling, `whereIn([])`, and `whereRaw`.
- `app:orders:deadlock` demonstrates transaction-required locks, rollback, and deterministic lock ordering.

## Articulate Concepts Demonstrated

- `transactional()`, manual transaction control, and rollback.
- Query builder `lock()` for `SELECT ... FOR UPDATE`.
- UUID primary keys.
- `#[ManyToOne]` and `#[OneToMany]`.
- Complex query builder usage with joins, aggregates, subqueries, and Criteria.

```php
#[Entity(tableName: 'orders')]
final class Order
{
    #[PrimaryKey(generator: PrimaryKey::GENERATOR_UUID_V4)]
    #[Property(maxLength: 36)]
    public ?string $id = null;

    #[OneToMany(ownedBy: 'order', targetEntity: OrderItem::class, lazy: true)]
    public array|Collection $items = [];
}
```

## Related Docs

- [Transactions and Locking](../../../documentation/transactions-locking/README.md)
- [Query Builder](../../../documentation/query-builder/README.md)
- [Relationships](../../../documentation/relationships/README.md)

## Known Caveats

- Generated primary keys, including UUIDs, are assigned during `flush()` — `$entity->id` is available after the database insert.
- `where('shipped_at', null)` compiles to `IS NULL` — `whereNull()` is equivalent and explicit.
- The deadlock command demonstrates the lock-ordering risk without creating a real concurrent database deadlock.
