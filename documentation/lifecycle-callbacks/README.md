# Lifecycle Callbacks

PrePersist, PostPersist, PreUpdate, PostUpdate, PreRemove, PostRemove, PostLoad.

**Runnable example:** [Lifecycle Callbacks](../../examples/lifecycle-callbacks/README.md)

## Attributes

```php
#[PrePersist]
public function onPrePersist(): void { }

#[PostPersist]
public function onPostPersist(): void { }

#[PreUpdate]
#[PostUpdate]
#[PreRemove]
#[PostRemove]
#[PostLoad]
```

## Use Cases

- Timestamps (created_at, updated_at)
- Audit logging
- Validation before persist
- Post-load normalization
