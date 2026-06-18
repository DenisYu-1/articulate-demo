# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Demo and documentation project for the [Articulate](https://github.com/denisyu-1/articulate) custom ORM library (`denis/articulate`). Symfony 8 console app — no HTTP routes, only CLI commands.

## Commands

```bash
# Run all tests
composer test

# Run single test file
php vendor/bin/phpunit tests/path/to/TestClass.php

# Run with coverage
composer test:coverage

# Start environment
docker compose up -d

# ORM schema management
docker compose exec php bin/console articulate:init    # create migrations table
docker compose exec php bin/console articulate:diff    # generate migration from entity diff
docker compose exec php bin/console articulate:migrate # run pending migrations

# Run example commands (require running DB)
docker compose exec php bin/console app:example:basic-crud
docker compose exec php bin/console app:example:relations
docker compose exec php bin/console app:example:advanced-querying
docker compose exec php bin/console app:example:transactions-locking
docker compose exec php bin/console app:example:pagination-sorting-soft-delete
docker compose exec php bin/console app:example:lifecycle-callbacks
docker compose exec php bin/console app:example:custom-types
```

## Architecture

### ORM Layer (`denis/articulate`)

Core services wired in `config/services.yaml`:
- `Articulate\Connection` — PDO wrapper, configured via `DATABASE_DSN/USER/PASSWORD` env vars
- `Articulate\Modules\EntityManager\EntityManager` — central ORM entry point; injected into all example commands
- `Articulate\Modules\Repository\RepositoryFactory` — creates typed repositories per entity
- `Articulate\Modules\Database\SchemaReader\MySqlSchemaReader` + `DatabaseSchemaComparator` — schema diffing for migrations
- `Articulate\Modules\Migrations\Generator\MigrationGenerator` — writes migration files to `migrations/`

### Entity Mapping

Entities live in `src/Entity/`. Mapping uses PHP 8 attributes:
- `#[Entity(tableName: '...')]` — marks class as ORM entity
- `#[PrimaryKey]` + `#[AutoIncrement]` — PK definition
- `#[Property(...)]` — column mapping (supports `name`, `maxLength`)
- `#[Index([...], unique: bool, concurrent: bool)]` — index definition
- Relations: `#[OneToMany]`, `#[ManyToOne]`, `#[OneToOne]`, `#[ManyToMany]`
- Relation collections typed as `array|Collection`

Entities are plain PHP classes (no base class). Entity path configured as `src/Entity` in `services.yaml`.

### Example Commands

All in `src/Command/Examples/`. Each is a Symfony console command (`#[AsCommand]`) that injects `EntityManager` directly and demonstrates one ORM feature. Commands are self-contained — they create, use, and clean up their own data.

### Migration Workflow

1. Add/modify entities in `src/Entity/`
2. `articulate:diff` — reads entity attributes + live DB schema, writes PHP migration class to `migrations/`
3. `articulate:migrate` — executes pending migrations in order

Migration namespace: `App\Migrations`.

### Environment

DB connection: `DATABASE_DSN` (mysql DSN), `DATABASE_USER`, `DATABASE_PASSWORD`. Default DB name: `articulate_test`. Test env overrides in `.env.test`.
