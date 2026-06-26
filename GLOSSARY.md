# Glossary

Short definitions for Articulate and ORM terms used throughout this repository.

| Term | Meaning | Read More |
|------|---------|-----------|
| Attribute mapping | PHP 8 attributes that describe how an entity class maps to a database table and columns. | [Entity Mapping](documentation/entity-mapping/README.md) |
| Concurrent index | An index marked with `concurrent: true` so migration generation can use non-blocking index creation where the database supports it. PostgreSQL concurrent index creation requires a non-transactional migration. | [Entity Mapping](documentation/entity-mapping/README.md) |
| Criteria | A reusable query filter object that can be applied to a query builder. | [Query Builder](documentation/query-builder/README.md) |
| Cursor pagination | Pagination based on the last seen ordered value instead of an offset. Useful for stable large-list traversal. | [Pagination and Filtering](documentation/pagination-filtering/README.md) |
| Entity | A plain PHP object mapped to a database table. | [Entity Mapping](documentation/entity-mapping/README.md) |
| EntityManager | The primary service used to persist, find, query, flush, and remove entities. | [Getting Started](documentation/getting-started/README.md) |
| Global filter | A query rule applied automatically, such as excluding soft-deleted rows. | [Pagination and Filtering](documentation/pagination-filtering/README.md) |
| Hydration | Turning database rows into PHP objects or scalar result structures. | [Performance](documentation/performance/README.md) |
| Identity map | The in-memory map that ensures repeated loads of the same entity class and primary key return the same PHP object instance. | [Performance](documentation/performance/README.md) |
| Index | Database metadata declared with `#[Index]` on an entity class to speed up lookups or enforce uniqueness. | [Entity Mapping](documentation/entity-mapping/README.md) |
| Lifecycle callback | Entity method called around persistence, update, removal, or load events. | [Lifecycle Callbacks](documentation/lifecycle-callbacks/README.md) |
| Migration | A versioned schema change generated from or applied to the database. | [Migrations](documentation/migrations/README.md) |
| Polymorphic relation | A relation where one association can target rows from multiple entity types. | [Relationships](documentation/relationships/README.md) |
| Projection | A smaller entity class mapped to the same table as a larger write model, often for read-specific use cases. | [Entity Mapping](documentation/entity-mapping/README.md) |
| Query builder | Fluent API for building database queries from PHP. | [Query Builder](documentation/query-builder/README.md) |
| Result cache | Cache for query result data from query-builder execution. | [Performance](documentation/performance/README.md) |
| Second-level cache | Cache for entity row data that can survive `EntityManager::clear()` and serve later `find()` calls. | [Performance](documentation/performance/README.md) |
| Soft delete | Marking a row as deleted, usually with `deleted_at`, while keeping it in the database. | [Pagination and Filtering](documentation/pagination-filtering/README.md) |
| Unit of Work | The change-tracking mechanism that computes inserts, updates, and deletes to execute on flush. | [Getting Started](documentation/getting-started/README.md) |
