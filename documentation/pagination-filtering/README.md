# Pagination and Filtering

Page through query results and apply global filters such as soft delete.

**Runnable feature commands:** `app:customers:browse`, `app:customers:soft-delete`

## What This Covers

- Offset pagination
- Cursor pagination
- Stable ordering
- Soft-delete filters
- Per-query filter bypass

## Offset Pagination

Offset pagination is simple and useful for small or administrative lists:

```php
$qb->limit(10)->offset(20);
```

It can become expensive or unstable on large, frequently changing datasets.

## Cursor Pagination

Cursor pagination uses the last seen ordered value:

```php
$qb
    ->cursor($cursor)
    ->cursorLimit(10)
    ->orderBy('id', 'ASC');

$result = $qb->getCursorPaginatedResult();
```

Use a unique or tie-broken ordering, such as `created_at` plus `id`, so records are not skipped or duplicated.

## Ordering

```php
$qb
    ->orderBy('created_at', 'DESC')
    ->orderBy('id', 'ASC');
```

Ordering matters for both user-facing list behavior and cursor stability.

## Soft Delete Filter

Register `SoftDeleteFilter` to automatically exclude records with a deleted marker such as `deleted_at`.

Use `withoutFilter('soft_delete')` on a query builder when an administrative query intentionally needs to include soft-deleted records.

```php
#[SoftDeleteable(fieldName: 'deleted_at', columnName: 'deleted_at')]
class Customer
{
    #[Property(name: 'deleted_at', nullable: true)]
    public ?string $deleted_at = null;
}
```

Source: [Customer](../../src/Features/CustomerAccounts/Entity/Customer.php)

## Common Pitfalls

- Soft-deleted rows are hidden from primary-key `find()` calls as well as list queries.
- `withoutFilter('soft_delete')` applies to the query builder where it is called; it does not turn the filter off globally.
- Cursor pagination needs a unique or tie-broken order, such as timestamp plus `id`.

## Navigation

Previous: [Query Builder](../query-builder/README.md)  
Base: [Documentation Index](../README.md)  
Next: [Lifecycle Callbacks](../lifecycle-callbacks/README.md)
