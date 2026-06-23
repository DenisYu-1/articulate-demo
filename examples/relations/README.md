# Relations

## What It Demonstrates

Entity associations, including one-to-one, one-to-many, many-to-many, and explicit relation loading.

## Prerequisites

- Docker services running.
- Migrations applied.

## Run Command

```bash
./examples/relations/run.sh
```

Equivalent command:

```bash
docker compose exec php bin/console app:example:relations
```

For feature-level catalog relations, run:

```bash
docker compose exec php bin/console app:catalog:crud
```

## Expected Output

The command seeds related rows and prints loaded relation data. The Catalog command also prints product/category many-to-many output.

## Related Source Files

- [RelationsExampleCommand](../../src/Command/Examples/RelationsExampleCommand.php)
- [Product](../../src/Features/Catalog/Entity/Product.php)
- [Category](../../src/Features/Catalog/Entity/Category.php)
- [Tagging feature README](../../src/Features/Tagging/README.md)

## Related Docs

- [Relationships](../../documentation/relationships/README.md)
- [Known Limitations](../../documentation/known-limitations/README.md)

## Navigation

Previous: [Migrations Workflow](../migrations-workflow/README.md)  
Base: [Examples Index](../README.md)  
Next: [Advanced Querying](../advanced-querying/README.md)
