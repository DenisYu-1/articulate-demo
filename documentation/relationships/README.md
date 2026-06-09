# Relationships

Define OneToOne, OneToMany, ManyToOne, ManyToMany, and polymorphic relations.

**Runnable example:** [Relations](../../examples/relations/README.md)

**Related examples:** [Basic CRUD](../../examples/basic-crud/README.md)

## Association Types

- `#[OneToOne]` – ownedBy / referencedBy
- `#[OneToMany]` – ownedBy, targetEntity
- `#[ManyToOne]` – targetEntity, referencedBy
- `#[ManyToMany]` – ownedBy, targetEntity, referencedBy

## Polymorphic

- `MorphTo`, `MorphOne`, `MorphMany`, `MorphToMany`, `MorphedByMany`

## Loading

Relations load automatically when accessed during hydration. Use `loadRelation($entity, $relationName)` for explicit loading.
