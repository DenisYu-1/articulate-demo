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
                customer_id INTEGER NOT NULL,
                status VARCHAR(32) NOT NULL,
                placed_at TIMESTAMP NOT NULL,
                shipped_at TIMESTAMP NULL,
                CONSTRAINT fk_orders_customer_id
                    FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT
            )'
        );
        $this->addSql('CREATE INDEX idx_orders_customer_status ON orders (customer_id, status)');
        $this->addSql('CREATE INDEX idx_orders_status_placed_at ON orders (status, placed_at)');

        $this->addSql(
            'CREATE TABLE order_items (
                id SERIAL PRIMARY KEY,
                order_id VARCHAR(36) NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price DOUBLE PRECISION NOT NULL,
                CONSTRAINT fk_order_items_order_id
                    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_order_items_product_id
                    FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT
            )'
        );
        $this->addSql('CREATE INDEX idx_order_items_order_id ON order_items (order_id)');
        $this->addSql('CREATE INDEX idx_order_items_product_id ON order_items (product_id)');
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE IF EXISTS order_items');
        $this->addSql('DROP TABLE IF EXISTS orders');
    }
}
