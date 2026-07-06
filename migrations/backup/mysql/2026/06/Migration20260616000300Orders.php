<?php

declare(strict_types=1);

namespace App\Migrations;

use Articulate\Modules\Migrations\Generator\BaseMigration;

final class Migration20260616000300Orders extends BaseMigration
{
    protected function up(): void
    {
        $this->addSql(
            'CREATE TABLE orders (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                customer_id INT UNSIGNED NOT NULL,
                status VARCHAR(32) NOT NULL,
                placed_at DATETIME NOT NULL,
                shipped_at DATETIME NULL,
                INDEX idx_orders_customer_status (customer_id, status),
                INDEX idx_orders_status_placed_at (status, placed_at),
                CONSTRAINT fk_orders_customer_id
                    FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT
            )'
        );

        $this->addSql(
            'CREATE TABLE order_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id VARCHAR(36) NOT NULL,
                product_id INT UNSIGNED NOT NULL,
                quantity INT NOT NULL,
                unit_price DOUBLE NOT NULL,
                INDEX idx_order_items_order_id (order_id),
                INDEX idx_order_items_product_id (product_id),
                CONSTRAINT fk_order_items_order_id
                    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_order_items_product_id
                    FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT
            )'
        );
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE IF EXISTS order_items');
        $this->addSql('DROP TABLE IF EXISTS orders');
    }
}
