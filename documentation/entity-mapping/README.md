# Entity Mapping

Define entities with PHP 8 attributes and map them to database tables.

**Runnable example:** [Basic CRUD](../../examples/basic-crud/README.md)

**Related examples:** [Relations](../../examples/relations/README.md), [Migrations Workflow](../../examples/migrations-workflow/README.md)

## Entity Attribute

```php
#[Entity]
#[Entity(tableName: 'custom_table')]
```

Use `#[Entity]` for default table name (pluralized class name) or `#[Entity(tableName: '...')]` for custom.

## Property Attributes

- `#[PrimaryKey]` – primary key column
- `#[AutoIncrement]` – auto-increment
- `#[Property]` – basic mapping
- `#[Property(name: 'col', type: 'string', nullable: true, maxLength: 255)]`

## Indexes

```php
#[Index(['email'], unique: true, concurrent: true)]
#[Index(['created_at', 'status'], concurrent: false)]
```

## Multiple Entities per Table

You can map different entity classes to the same table (e.g. `User` vs `LoginUser` for read-only login context).
