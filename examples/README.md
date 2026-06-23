# Examples

Runnable examples mirror the documentation path. Each page explains the command, expected output shape, source files, related docs, and navigation.

| Step | Example | Related Guide | Run Command |
|------|---------|---------------|-------------|
| 1 | [Basic CRUD](basic-crud/README.md) | [Getting Started](../documentation/getting-started/README.md), [Entity Mapping](../documentation/entity-mapping/README.md) | `./examples/basic-crud/run.sh` |
| 2 | [Migrations Workflow](migrations-workflow/README.md) | [Migrations](../documentation/migrations/README.md) | `./examples/migrations-workflow/run.sh` |
| 3 | [Relations](relations/README.md) | [Relationships](../documentation/relationships/README.md) | `./examples/relations/run.sh` |
| 4 | [Advanced Querying](advanced-querying/README.md) | [Query Builder](../documentation/query-builder/README.md) | `./examples/advanced-querying/run.sh` |
| 5 | [Pagination, Sorting, Soft Delete](pagination-sorting-soft-delete/README.md) | [Pagination and Filtering](../documentation/pagination-filtering/README.md) | `./examples/pagination-sorting-soft-delete/run.sh` |
| 6 | [Lifecycle Callbacks](lifecycle-callbacks/README.md) | [Lifecycle Callbacks](../documentation/lifecycle-callbacks/README.md) | `./examples/lifecycle-callbacks/run.sh` |
| 7 | [Custom Types](custom-types/README.md) | [Custom Types](../documentation/custom-types/README.md) | `./examples/custom-types/run.sh` |
| 8 | [Transactions and Locking](transactions-locking/README.md) | [Transactions and Locking](../documentation/transactions-locking/README.md) | `./examples/transactions-locking/run.sh` |
| 9 | [Multiple Unit of Work](multiple-unit-of-work/README.md) | [Performance](../documentation/performance/README.md) | `./examples/multiple-unit-of-work/run.sh` |

## Command Matrix

| Command | Feature | Purpose | Required Data State | Related Docs |
|---------|---------|---------|---------------------|--------------|
| `app:example:basic-crud` | Legacy example | Minimal persist/find/update/remove flow. | Migrations applied. | [Getting Started](../documentation/getting-started/README.md), [Entity Mapping](../documentation/entity-mapping/README.md) |
| `app:catalog:crud` | [Catalog](../src/Features/Catalog/README.md) | Product/category CRUD, many-to-many categories, custom status conversion, constraint failures. | Migrations applied. Seeds its own rows. | [Entity Mapping](../documentation/entity-mapping/README.md), [Custom Types](../documentation/custom-types/README.md), [Relationships](../documentation/relationships/README.md) |
| `app:catalog:query` | [Catalog](../src/Features/Catalog/README.md) | Basic query builder filters, ordering, `whereIn`, and offset pagination. | Migrations applied. Seeds catalog rows. | [Query Builder](../documentation/query-builder/README.md) |
| `app:example:migrations-workflow` | Legacy example | Prints migration workflow steps. | Docker/database available for real migrate commands. | [Migrations](../documentation/migrations/README.md) |
| `articulate:init` | Migrations | Creates migration tracking table. | Database exists. | [Migrations](../documentation/migrations/README.md) |
| `articulate:migrate` | Migrations | Applies checked-in migrations. | `articulate:init` has run. | [Migrations](../documentation/migrations/README.md) |
| `articulate:diff` | Migrations | Generates migrations from entity/schema differences. | Use after entity metadata changes. Not required for clean checkout. | [Migrations](../documentation/migrations/README.md), [Known Limitations](../documentation/known-limitations/README.md) |
| `app:example:relations` | Legacy example | Basic one-to-one, one-to-many, many-to-many relation flow. | Migrations applied. | [Relationships](../documentation/relationships/README.md) |
| `app:tagging:demo` | [Tagging](../src/Features/Tagging/README.md) | Polymorphic tags over orders and customers. | Migrations applied. Seeds required rows. | [Relationships](../documentation/relationships/README.md), [Known Limitations](../documentation/known-limitations/README.md) |
| `app:example:advanced-querying` | Legacy example | Query builder joins, aggregates, subqueries, and Criteria. | Migrations applied. | [Query Builder](../documentation/query-builder/README.md) |
| `app:orders:query` | [Orders](../src/Features/Orders/README.md) | Order joins, aggregates, `whereExists`, Criteria, null handling, and raw predicates. | Migrations applied. Seeds orders/items. | [Query Builder](../documentation/query-builder/README.md), [Known Limitations](../documentation/known-limitations/README.md) |
| `app:customers:browse` | [Customer Accounts](../src/Features/CustomerAccounts/README.md) | Repository queries, offset pagination, cursor pagination, and relation loading. | Migrations applied. Seeds customers. | [Pagination and Filtering](../documentation/pagination-filtering/README.md) |
| `app:customers:soft-delete` | [Customer Accounts](../src/Features/CustomerAccounts/README.md) | Soft-delete filter behavior and `withoutFilter`. | Migrations applied. Seeds customers. | [Pagination and Filtering](../documentation/pagination-filtering/README.md) |
| `app:customers:lifecycle` | [Customer Accounts](../src/Features/CustomerAccounts/README.md) | Lifecycle callback order and audit output. | Migrations applied. Seeds customer/address rows. | [Lifecycle Callbacks](../documentation/lifecycle-callbacks/README.md) |
| `app:customers:cross-entity` | [Customer Accounts](../src/Features/CustomerAccounts/README.md) | Same-table projection identity map and L2 cache behavior. | Migrations applied. Seeds customer rows. | [Performance](../documentation/performance/README.md) |
| `app:example:custom-types` | Legacy example | Custom converter basics. | Migrations applied. | [Custom Types](../documentation/custom-types/README.md) |
| `app:orders:place` | [Orders](../src/Features/Orders/README.md) | Transactional order placement and stock locking. | Migrations applied. Seeds customer/product/stock rows. | [Transactions and Locking](../documentation/transactions-locking/README.md) |
| `app:orders:deadlock` | [Orders](../src/Features/Orders/README.md) | Lock ordering, rollback, and deadlock mitigation. | Migrations applied. Seeds stock rows. | [Transactions and Locking](../documentation/transactions-locking/README.md) |
| `app:analytics:report` | [Analytics](../src/Features/Analytics/README.md) | Projection reports, aggregates, result cache, and query logging. | Migrations applied. Seeds orders/items/products. | [Performance](../documentation/performance/README.md), [Query Builder](../documentation/query-builder/README.md) |
| `app:analytics:batch` | [Analytics](../src/Features/Analytics/README.md) | Bounded batch reads and identity-map clearing. | Migrations applied. Seeds analytics rows. | [Performance](../documentation/performance/README.md) |
| `app:import:run` | [Bulk Import](../src/Features/BulkImport/README.md) | Naive vs scoped-unit-of-work import memory behavior. | Migrations applied. Use `--count` for quick smoke runs. | [Performance](../documentation/performance/README.md) |

Start from the [repository README](../README.md) for the full learning path.
