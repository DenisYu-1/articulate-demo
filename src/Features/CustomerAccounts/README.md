# Customer Accounts Feature

## Feature Purpose

Customer Accounts models registration, profile browsing, lifecycle callbacks, soft deletion, custom repositories, and same-table projections. It is the main feature for demonstrating behavior that happens around ordinary CRUD.

## Entities

- `Customer` maps `customers`, uses `CustomerRepository`, lifecycle callbacks, soft-delete metadata, and a one-to-one address relation.
- `CustomerSummary` maps the same `customers` table with only list-view fields.
- `Address` maps `customer_addresses`.
- `CustomerAuditEntry` records callback-driven audit output.

## Commands

- `app:customers:lifecycle` registers, updates, loads, and soft-deletes a customer while printing callback order.
- `app:customers:browse` uses `CustomerRepository`, offset pagination, cursor pagination, and explicit address loading.
- `app:customers:soft-delete` shows global soft-delete filtering, `withoutFilter('soft_delete')`, and reactivation.
- `app:customers:cross-entity` demonstrates identity-map and second-level-cache behavior when multiple classes map the same row.

## Articulate Concepts Demonstrated

- Lifecycle callbacks from `PrePersist` through `PostLoad`.
- `#[SoftDeleteable]` and the soft-delete filter.
- `#[Entity(repositoryClass: ...)]` and repository-specific Criteria.
- Same-table projection entities.
- Identity map and second-level cache behavior for sibling projections.

```php
#[Entity(tableName: 'customers', repositoryClass: CustomerRepository::class)]
#[SoftDeleteable(fieldName: 'deleted_at', columnName: 'deleted_at')]
class Customer
{
    #[PreRemove]
    public function onPreRemove(): void
    {
        $this->markDeleted();
    }
}
```

## Related Docs

- [Lifecycle Callbacks](../../../documentation/lifecycle-callbacks/README.md)
- [Pagination and Filtering](../../../documentation/pagination-filtering/README.md)
- [Performance](../../../documentation/performance/README.md)

## Known Caveats

- The soft-delete command uses a managed `deleted_at` update path because current soft-delete scheduling writes a `DateTimeImmutable` before the demo's string converter path runs.
- Address relation loading is explicit because lazy relation proxies cannot be flushed safely in the installed dependency.
- Snake_case fields keep explicit `#[Property(name: ...)]` mappings until hydrator fallback behavior is rechecked.
