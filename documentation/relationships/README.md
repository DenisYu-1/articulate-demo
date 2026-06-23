# Relationships

Define associations between mapped entities, from simple ownership to polymorphic links.

**Runnable example:** [Relations](../../examples/relations/README.md)  
**Related example:** [Basic CRUD](../../examples/basic-crud/README.md)

## What This Covers

- One-to-one, one-to-many, many-to-one, and many-to-many relations
- Owned and inverse sides
- Polymorphic relation attributes
- Explicit relation loading

## Association Types

- `#[OneToOne]` maps one entity to exactly one related entity.
- `#[OneToMany]` maps one parent to many children.
- `#[ManyToOne]` maps many children to one parent.
- `#[ManyToMany]` maps both sides through a pivot table.

Relation-owned foreign key columns should be mapped by the relation only. Do not map the same physical column as both a scalar `#[Property]` and a relation on the same entity.

## Polymorphic Relations

Articulate also exposes polymorphic relation attributes:

- `MorphTo`
- `MorphOne`
- `MorphMany`
- `MorphToMany`
- `MorphedByMany`

The tagging demo uses polymorphic many-to-many tags over customers and orders.

## Loading

Relations can be loaded during hydration depending on metadata and query path. Use `loadRelation($entity, $relationName)` when a demo needs to make relation loading explicit.

```php
#[ManyToOne(targetEntity: Customer::class, column: 'customer_id', nullable: false)]
public ?Customer $customer = null;

#[OneToMany(ownedBy: 'order', targetEntity: OrderItem::class, lazy: true)]
public array|Collection $items = [];
```

Source: [Order](../../src/Features/Orders/Entity/Order.php)

## Common Pitfalls

- Map a foreign key column either as a relation or as a scalar property on the same entity, not both.
- Prefer explicit `loadRelation()` in demo code when relation loading behavior is the concept being shown.
- Polymorphic many-to-many metadata is demonstrated in Tagging, but current relation loading gaps are documented in [Known Limitations](../known-limitations/README.md).

## Navigation

Previous: [Migrations](../migrations/README.md)  
Base: [Documentation Index](../README.md)  
Next: [Query Builder](../query-builder/README.md)
