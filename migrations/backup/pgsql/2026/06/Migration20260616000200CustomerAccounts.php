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
                id SERIAL PRIMARY KEY,
                street VARCHAR(160) NOT NULL,
                city VARCHAR(80) NOT NULL,
                state VARCHAR(80) NULL,
                postal_code VARCHAR(32) NOT NULL,
                country VARCHAR(80) NOT NULL,
                customer_id INTEGER NULL
            )'
        );
        $this->addSql('CREATE INDEX idx_customer_addresses_customer_id ON customer_addresses (customer_id)');

        $this->addSql(
            'CREATE TABLE customers (
                id SERIAL PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(255) NOT NULL,
                status VARCHAR(32) NOT NULL,
                address_id INTEGER NULL,
                registered_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                deleted_at TIMESTAMP NULL,
                CONSTRAINT uniq_customers_email UNIQUE (email),
                CONSTRAINT fk_customers_address_id
                    FOREIGN KEY (address_id) REFERENCES customer_addresses (id) ON DELETE SET NULL
            )'
        );
        $this->addSql('CREATE INDEX idx_customers_status_registered_at ON customers (status, registered_at)');

        $this->addSql(
            'ALTER TABLE customer_addresses
                ADD CONSTRAINT fk_customer_addresses_customer_id
                FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL'
        );

        $this->addSql(
            'CREATE TABLE customer_audit_entries (
                id SERIAL PRIMARY KEY,
                customer_id INTEGER NULL,
                action VARCHAR(64) NOT NULL,
                message VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL,
                CONSTRAINT fk_customer_audit_entries_customer_id
                    FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
            )'
        );
        $this->addSql('CREATE INDEX idx_customer_audit_entries_customer_id ON customer_audit_entries (customer_id)');
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE IF EXISTS customer_audit_entries');
        $this->addSql('ALTER TABLE customer_addresses DROP CONSTRAINT IF EXISTS fk_customer_addresses_customer_id');
        $this->addSql('ALTER TABLE customers DROP CONSTRAINT IF EXISTS fk_customers_address_id');
        $this->addSql('DROP TABLE IF EXISTS customers');
        $this->addSql('DROP TABLE IF EXISTS customer_addresses');
    }
}
