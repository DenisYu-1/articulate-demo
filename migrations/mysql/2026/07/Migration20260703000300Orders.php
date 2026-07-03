<?php

declare(strict_types=1);

namespace App\Migrations;

use Articulate\Modules\Migrations\Generator\BaseMigration;

class Migration20260703000300Orders extends BaseMigration
{
    protected function up(): void
    {
        $this->addSql('CREATE TABLE `orders` (`id` VARCHAR(36)  NOT NULL, `status` VARCHAR(32) NOT NULL, `placed_at` VARCHAR(255) NOT NULL, `shipped_at` VARCHAR(255), `customer_id` INT UNSIGNED NOT NULL, PRIMARY KEY (`id`), INDEX `idx_orders_customer_status` (`customer_id`, `status`), INDEX `idx_orders_status_placed_at` (`status`, `placed_at`), CONSTRAINT `fk_orders_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`))');
        $this->addSql('CREATE TABLE `order_items` (`id` INT UNSIGNED AUTO_INCREMENT NOT NULL, `product_id` INT NOT NULL, `quantity` INT NOT NULL, `unit_price` DOUBLE NOT NULL, `order_id` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`), INDEX `idx_order_items_order_id` (`order_id`), INDEX `idx_order_items_product_id` (`product_id`), CONSTRAINT `fk_order_items_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`))');
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE `order_items`');
        $this->addSql('DROP TABLE `orders`');
    }
}
