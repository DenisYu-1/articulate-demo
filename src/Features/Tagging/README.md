# Tagging Feature

## Feature Purpose

Tagging demonstrates polymorphic many-to-many metadata over customers and orders. It uses one tag model and a shared pivot table so different entity types can receive the same tag vocabulary.

## Entities

- `Tag` maps `tags` and declares inverse polymorphic collections for orders and customers.
- `TaggableOrder` maps `orders` as a tagging projection.
- `TaggableCustomer` maps `customers` as a tagging projection.
- `taggables` is a pivot table with `tag_id`, `taggable_type`, and `taggable_id`.

## Commands

- `app:tagging:demo` creates tags, attaches them to orders and customers through the pivot table, queries tags from each side, and shows inverse lookup behavior.

## Articulate Concepts Demonstrated

- `#[MorphToMany]` and `#[MorphedByMany]`.
- `MorphTypeRegistry` aliases such as `order` and `customer`.
- Same-table projection entities used only for a specific feature.
- Pivot-table querying for inspecting the stored morph aliases.

```php
#[Entity(tableName: 'tags')]
final class Tag
{
    #[MorphedByMany(targetEntity: TaggableOrder::class, name: 'taggable', targetIdColumn: 'tag_id')]
    public array|Collection $orders = [];
}
```

## Related Docs

- [Relationships](../../../documentation/relationships/README.md)
- [Known Limitations](../../../documentation/known-limitations/README.md)

## Known Caveats

- `loadRelation()` supports these polymorphic relation objects; the command also queries the `taggables` pivot directly to show the stored aliases.
- `targetIdColumn: 'tag_id'` is explicit because the default would be `tags_id`.
- `taggable_id` is stored as `VARCHAR(36)` so one column can hold integer customer ids and UUID order ids.
