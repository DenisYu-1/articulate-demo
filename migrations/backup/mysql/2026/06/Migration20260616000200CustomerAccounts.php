<?php

declare(strict_types=1);

namespace App\Migrations;

use Articulate\Modules\Migrations\Generator\BaseMigration;

final class Migration20260616000200CustomerAccounts extends BaseMigration
{
    protected function up(): void
    {
        $this->addSql(
            'CREATE TABLE customer_addresses (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                street VARCHAR(160) NOT NULL,
                city VARCHAR(80) NOT NULL,
                state VARCHAR(80) NULL,
                postal_code VARCHAR(32) NOT NULL,
                country VARCHAR(80) NOT NULL,
                customer_id INT UNSIGNED NULL,
                INDEX idx_customer_addresses_customer_id (customer_id)
            )'
        );

        $this->addSql(
            'CREATE TABLE customers (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(255) NOT NULL,
                status VARCHAR(32) NOT NULL,
                address_id INT UNSIGNED NULL,
                registered_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                CONSTRAINT uniq_customers_email UNIQUE (email),
                INDEX idx_customers_status_registered_at (status, registered_at),
                CONSTRAINT fk_customers_address_id
                    FOREIGN KEY (address_id) REFERENCES customer_addresses (id) ON DELETE SET NULL
            )'
        );

        $this->addSql(
            'ALTER TABLE customer_addresses
                ADD CONSTRAINT fk_customer_addresses_customer_id
                FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL'
        );

        $this->addSql(
            'CREATE TABLE customer_audit_entries (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                customer_id INT UNSIGNED NULL,
                action VARCHAR(64) NOT NULL,
                message VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_customer_audit_entries_customer_id (customer_id),
                CONSTRAINT fk_customer_audit_entries_customer_id
                    FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
            )'
        );
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE IF EXISTS customer_audit_entries');
        $this->addSql('ALTER TABLE customer_addresses DROP FOREIGN KEY fk_customer_addresses_customer_id');
        $this->addSql('ALTER TABLE customers DROP FOREIGN KEY fk_customers_address_id');
        $this->addSql('DROP TABLE IF EXISTS customers');
        $this->addSql('DROP TABLE IF EXISTS customer_addresses');
    }
}
