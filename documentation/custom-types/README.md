# Custom Types

Register PHP↔DB type converters via TypeRegistry.

**Runnable example:** [Custom Types](../../examples/custom-types/README.md)

## TypeConverterInterface

```php
interface TypeConverterInterface
{
    public function convertToPHP(mixed $value): mixed;
    public function convertToDB(mixed $value): mixed;
}
```

## Built-in Converters

- `BoolTypeConverter`
- `DateTimeTypeConverter`
- `PointTypeConverter`

## Registration

```php
$typeRegistry->registerType('point', new PointTypeConverter());
$typeRegistry->registerClassMapping(Point::class, 'point');
```
