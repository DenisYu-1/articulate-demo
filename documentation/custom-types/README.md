# Custom Types

Convert between PHP-specific values and database storage values.

**Runnable feature command:** `app:catalog:crud`

## What This Covers

- `TypeConverterInterface`
- Built-in converters
- Registering custom type names
- Mapping PHP classes to converter names

## TypeConverterInterface

```php
interface TypeConverterInterface
{
    public function convertToPHP(mixed $value): mixed;
    public function convertToDB(mixed $value): mixed;
}
```

Use a converter when the database representation is not the same shape as the PHP representation. Common examples are enums, value objects, booleans, dates, and geometry-like values.

## Built-In Converters

- `BoolTypeConverter`
- `DateTimeTypeConverter`
- `PointTypeConverter`

## Registration

```php
$typeRegistry->registerType('point', new PointTypeConverter());
$typeRegistry->registerClassMapping(Point::class, 'point');
```

Once registered, entity properties can use the type name through `#[Property(type: 'point')]` or class mapping.

The Catalog feature keeps product status as a database string and exposes enum helpers:

```php
public function setStatus(ProductStatus $status): void
{
    $this->status = (new ProductStatusConverter())->convertToDatabase($status);
}

public function statusEnum(): ProductStatus
{
    return (new ProductStatusConverter())->convertToPHP($this->status);
}
```

Source: [Product](../../src/Features/Catalog/Entity/Product.php)

## Common Pitfalls

- Register converters before hydrating or persisting values that require them.
- Keep database values stable even when PHP enum or value-object names change.
- Nullable custom fields still need converter code that handles `null` when the column allows it.

## Navigation

Previous: [Lifecycle Callbacks](../lifecycle-callbacks/README.md)  
Base: [Documentation Index](../README.md)  
Next: [Transactions and Locking](../transactions-locking/README.md)
