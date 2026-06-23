# Lifecycle Callbacks

Run entity methods around persistence, update, removal, and hydration events.

**Runnable example:** [Lifecycle Callbacks](../../examples/lifecycle-callbacks/README.md)

## What This Covers

- Callback attributes
- Common timestamp and audit use cases
- Callback order during persistence
- Post-load behavior during hydration

## Attributes

```php
#[PrePersist]
public function onPrePersist(): void
{
}

#[PostPersist]
public function onPostPersist(): void
{
}
```

Available callback attributes:

- `#[PrePersist]`
- `#[PostPersist]`
- `#[PreUpdate]`
- `#[PostUpdate]`
- `#[PreRemove]`
- `#[PostRemove]`
- `#[PostLoad]`

## Use Cases

- Set `created_at` or `updated_at` timestamps.
- Write audit records after persistence.
- Validate entity state before flush.
- Normalize values after loading from the database.
- Mark soft-delete columns before removal.

```php
#[PreUpdate]
public function onPreUpdate(): void
{
    $this->updated_at = self::now();
}

#[PostLoad]
public function onPostLoad(): void
{
    $this->record('PostLoad');
}
```

Source: [Customer](../../src/Features/CustomerAccounts/Entity/Customer.php)

## Common Pitfalls

- Throwing from `PrePersist` prevents the entity from being written.
- `PostLoad` can run on `find()`, query-builder hydration, and explicit relation loading.
- Callback methods should keep side effects narrow; the demo audit writer is explicit so command output stays understandable.

## Navigation

Previous: [Pagination and Filtering](../pagination-filtering/README.md)  
Base: [Documentation Index](../README.md)  
Next: [Custom Types](../custom-types/README.md)
