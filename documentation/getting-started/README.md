# Getting Started

Introduction to Articulate ORM and project setup.

**Runnable example:** [Basic CRUD](../../examples/basic-crud/README.md)

## Installation

Add the package via Composer:

```bash
composer require denisyu-1/articulate
```

## Configuration

Configure the database connection in your environment:

```env
DATABASE_DSN=mysql:host=mysql;dbname=articulate_test;charset=utf8mb4
DATABASE_USER=user
DATABASE_PASSWORD=userpassword
```

## Services

Register `Connection` and `EntityManager` in your DI container. The demo project uses Symfony's `services.yaml`:

- `Articulate\Connection` – DSN, user, password
- `Articulate\Modules\EntityManager\EntityManager` – depends on Connection

## Next Steps

- [Entity Mapping](../entity-mapping/README.md) – Define entities and properties
- [Relationships](../relationships/README.md) – Define relations between entities
