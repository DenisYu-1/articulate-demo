# Pagination and Filtering

Offset pagination, cursor pagination, ordering, and global filters.

**Runnable example:** [Pagination, Sorting, Soft Delete](../../examples/pagination-sorting-soft-delete/README.md)

## Offset Pagination

```php
$qb->limit(10)->offset(20);
```

## Cursor Pagination

```php
$qb->cursor($cursor)->cursorLimit(10)->orderBy('id', 'ASC');
$result = $qb->getCursorPaginatedResult();
```

## Ordering

```php
$qb->orderBy('created_at', 'DESC')->orderBy('id', 'ASC');
```

## Soft Delete Filter

Register `SoftDeleteFilter` and use `withoutFilter('soft_delete')` to include soft-deleted records.
