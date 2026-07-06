<?php

declare(strict_types=1);

namespace App\Migrations;

use Articulate\Modules\Migrations\Generator\BaseMigration;

class Migration20260703000100Catalog extends BaseMigration
{
    protected function isTransactional(): bool
    {
        return false;
    }

    protected function up(): void
    {
        $this->addSql('CREATE TABLE "categories" ("id" INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL, "name" VARCHAR(120) NOT NULL, "slug" VARCHAR(120) NOT NULL, PRIMARY KEY ("id"))');
        $this->addSql('CREATE UNIQUE INDEX "uniq_categories_slug" ON "categories" ("slug")');
        $this->addSql('CREATE TABLE "products" ("id" INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL, "sku" VARCHAR(64) NOT NULL, "product_name" VARCHAR(160) NOT NULL, "slug" VARCHAR(160) NOT NULL, "description" VARCHAR(500), "status" VARCHAR(32) NOT NULL, "category_id" INTEGER, "price" DOUBLE PRECISION NOT NULL, PRIMARY KEY ("id"))');
        $this->addSql('CREATE UNIQUE INDEX CONCURRENTLY "uniq_products_sku" ON "products" ("sku")');
        $this->addSql('CREATE INDEX CONCURRENTLY "idx_products_category_status" ON "products" ("category_id", "status")');
        $this->addSql('CREATE TABLE "product_stock" ("product_id" INTEGER NOT NULL, "stock" INTEGER NOT NULL, PRIMARY KEY ("product_id"))');
        $this->addSql('CREATE TABLE "categories_products" ("products_id" INTEGER NOT NULL, "categories_id" INTEGER NOT NULL, "is_primary" INTEGER NOT NULL DEFAULT \'0\', "position" INTEGER NOT NULL DEFAULT \'0\', "assigned_at" TIMESTAMP NOT NULL, "updated_at" TIMESTAMP NOT NULL, PRIMARY KEY ("products_id", "categories_id"), CONSTRAINT "fk_categories_products_products_id" FOREIGN KEY ("products_id") REFERENCES "products"("id"), CONSTRAINT "fk_categories_products_categories_id" FOREIGN KEY ("categories_id") REFERENCES "categories"("id"))');
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE "categories_products"');
        $this->addSql('DROP TABLE "product_stock"');
        $this->addSql('DROP TABLE "products"');
        $this->addSql('DROP TABLE "categories"');
    }
}
