# Catalog Feature

## Feature Purpose

Catalog is the product and category model used by the rest of the demo. It keeps mapping examples small while still showing real constraints such as unique SKUs, required slugs, category assignment, stock rows, and enum-like status conversion.

## Entities

- `Product` maps `products` with primary key, custom column names, nullable and required fields, unique and composite indexes, and a many-to-many category relation.
- `Category` maps `categories` and owns the inverse product collection through `categories_products`.
- `InventorySlot` maps `product_stock` for inventory quantities used by the Orders feature.
- `ProductStatus` is a PHP enum converted to the database string stored in `Product::$status`.

## Commands

- `app:catalog:crud` creates categories/products, demonstrates persist/find/update/delete, loads the product-category relation, and prints required-column and duplicate-SKU failures.
- `app:catalog:query` demonstrates `find`, `findBy`, `where`, `whereIn`, ordering, and offset pagination.

## Articulate Concepts Demonstrated

- `#[Entity]`, `#[PrimaryKey]`, `#[AutoIncrement]`, `#[Property]`, and `#[Index]`.
- `#[ManyToMany]` with a mapping table and extra pivot columns.
- Custom type conversion through `ProductStatusConverter`.
- Basic query builder reads and pagination.

```php
#[Entity(tableName: 'products')]
#[Index(['sku'], unique: true, name: 'uniq_products_sku')]
#[Index(['categoryId', 'status'], name: 'idx_products_category_status')]
final class Product
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(name: 'product_name', maxLength: 160)]
    public string $name;
}
```

## Related Docs and Examples

- [Entity Mapping](../../../documentation/entity-mapping/README.md)
- [Custom Types](../../../documentation/custom-types/README.md)
- [Relationships](../../../documentation/relationships/README.md)
- [Basic CRUD](../../../examples/basic-crud/README.md)
- [Advanced Querying](../../../examples/advanced-querying/README.md)

## Known Caveats

- Required-column and duplicate-SKU failures intentionally bubble up as database errors, so exact messages vary by driver.
- `InventorySlot` shares the `product_stock` table with Orders' `StockLock` projection.
