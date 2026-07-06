<?php

declare(strict_types=1);

namespace App\Migrations;

use Articulate\Modules\Migrations\Generator\BaseMigration;

final class Migration20260616000100Catalog extends BaseMigration
{
    protected function up(): void
    {
        $this->addSql(
            'CREATE TABLE categories (
                id SERIAL PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(120) NOT NULL,
                CONSTRAINT uniq_categories_slug UNIQUE (slug)
            )'
        );

        $this->addSql(
            'CREATE TABLE products (
                id SERIAL PRIMARY KEY,
                sku VARCHAR(64) NOT NULL,
                product_name VARCHAR(160) NOT NULL,
                slug VARCHAR(160) NOT NULL,
                description VARCHAR(500) NULL,
                status VARCHAR(32) NOT NULL,
                category_id INTEGER NULL,
                price DOUBLE PRECISION NOT NULL,
                CONSTRAINT uniq_products_sku UNIQUE (sku)
            )'
        );

        $this->addSql('CREATE INDEX idx_products_category_status ON products (category_id, status)');

        $this->addSql(
            'CREATE TABLE product_stock (
                product_id INTEGER NOT NULL PRIMARY KEY,
                stock INTEGER NOT NULL,
                CONSTRAINT fk_product_stock_product_id
                    FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
            )'
        );

        $this->addSql(
            'CREATE TABLE categories_products (
                products_id INTEGER NOT NULL,
                categories_id INTEGER NOT NULL,
                PRIMARY KEY (products_id, categories_id),
                CONSTRAINT fk_categories_products_products_id
                    FOREIGN KEY (products_id) REFERENCES products (id) ON DELETE CASCADE,
                CONSTRAINT fk_categories_products_categories_id
                    FOREIGN KEY (categories_id) REFERENCES categories (id) ON DELETE CASCADE
            )'
        );
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE IF EXISTS categories_products');
        $this->addSql('DROP TABLE IF EXISTS product_stock');
        $this->addSql('DROP TABLE IF EXISTS products');
        $this->addSql('DROP TABLE IF EXISTS categories');
    }
}
