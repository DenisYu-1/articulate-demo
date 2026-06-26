<?php

declare(strict_types=1);

namespace App\Migrations;

use Articulate\Modules\Migrations\Generator\BaseMigration;

class Migration20260625000100Catalog extends BaseMigration
{
    protected function up(): void
    {
        $this->addSql('CREATE TABLE `categories` (`id` INT UNSIGNED AUTO_INCREMENT NOT NULL, `name` VARCHAR(120) NOT NULL, `slug` VARCHAR(120) NOT NULL, PRIMARY KEY (`id`), UNIQUE INDEX `uniq_categories_slug` (`slug`))');
        $this->addSql('CREATE TABLE `products` (`id` INT UNSIGNED AUTO_INCREMENT NOT NULL, `sku` VARCHAR(64) NOT NULL, `product_name` VARCHAR(160) NOT NULL, `slug` VARCHAR(160) NOT NULL, `description` VARCHAR(500), `status` VARCHAR(32) NOT NULL, `category_id` INT, `price` DOUBLE NOT NULL, PRIMARY KEY (`id`), UNIQUE INDEX `uniq_products_sku` (`sku`), INDEX `idx_products_category_status` (`category_id`, `status`))');
        $this->addSql('CREATE TABLE `product_stock` (`product_id` INT UNSIGNED NOT NULL, `stock` INT NOT NULL, PRIMARY KEY (`product_id`))');
        $this->addSql('CREATE TABLE `categories_products` (`products_id` INT UNSIGNED NOT NULL, `categories_id` INT UNSIGNED NOT NULL, `is_primary` INT NOT NULL DEFAULT \'0\', `position` INT NOT NULL DEFAULT \'0\', `assigned_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL, PRIMARY KEY (`products_id`, `categories_id`), CONSTRAINT `fk_categories_products_products_id` FOREIGN KEY (`products_id`) REFERENCES `products`(`id`), CONSTRAINT `fk_categories_products_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories`(`id`))');
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE `categories_products`');
        $this->addSql('DROP TABLE `product_stock`');
        $this->addSql('DROP TABLE `products`');
        $this->addSql('DROP TABLE `categories`');
    }
}
