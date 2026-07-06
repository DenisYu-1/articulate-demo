# Entity Mapping

Map plain PHP classes to database tables with PHP 8 attributes.

**Runnable feature commands:** `app:catalog:crud`, `app:customers:cross-entity`  
**Related feature commands:** `app:tagging:demo`, `articulate:diff`

## What This Covers

- `#[Entity]` table mapping
- `#[PrimaryKey]`, `#[AutoIncrement]`, and `#[Property]`
- Column names, nullability, max lengths, and DB types
- Index metadata
- Multiple entity classes mapped to the same table

## Entity Attribute

```php
#[Entity]
#[Entity(tableName: 'custom_table')]
```

Use `#[Entity]` for the default table name or `#[Entity(tableName: '...')]` when the physical table name should be explicit.

## Property Attributes

- `#[PrimaryKey]` marks the primary key column.
- `#[AutoIncrement]` delegates integer ID assignment to the database.
- `#[Property]` maps a PHP property to a database column.
- `#[Property(name: 'created_at', type: 'datetime', nullable: true, maxLength: 255)]` customizes the physical column metadata.

## Indexes

```php
#[Index(['email'], unique: true, concurrent: true)]
#[Index(['created_at', 'status'], concurrent: false)]
```

Indexes are consumed by schema diffing and migration generation. PostgreSQL `CREATE INDEX CONCURRENTLY` cannot run inside a transaction, so migrations that create concurrent indexes must disable transactional execution.

## Same-Table Projections

Different entity classes can map to the same table. The demo uses this for read models such as customer summaries and analytics snapshots where a feature only needs a subset of columns.

```php
#[Entity(tableName: 'products')]
#[Index(['sku'], unique: true, name: 'uniq_products_sku')]
final class Product
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(name: 'product_name', maxLength: 160)]
    public string $name;
}
```

Source: [Product](../../src/Features/Catalog/Entity/Product.php)

## Common Pitfalls

- A required PHP property should match a required database column. If `slug` is left unset in the Catalog demo, the database raises the final constraint error.
- When the PHP property name differs from the column name, set `#[Property(name: '...')]` explicitly.
- Same-table projections are separate entity classes. Loading `Customer` and `CustomerSummary` for the same row gives two independent PHP objects.

## Navigation

Previous: [Getting Started](../getting-started/README.md)  
Base: [Documentation Index](../README.md)  
Next: [Migrations](../migrations/README.md)
