<?php

declare(strict_types=1);

namespace App\Migrations;

use Articulate\Modules\Migrations\Generator\BaseMigration;

class Migration20260703000200CustomerAccounts extends BaseMigration
{
    protected function up(): void
    {
        $this->addSql('CREATE TABLE `customer_addresses` (`id` INT UNSIGNED AUTO_INCREMENT NOT NULL, `street` VARCHAR(160) NOT NULL, `city` VARCHAR(80) NOT NULL, `state` VARCHAR(80), `postal_code` VARCHAR(32) NOT NULL, `country` VARCHAR(80) NOT NULL, `customer_id` INT, PRIMARY KEY (`id`), INDEX `idx_customer_addresses_customer_id` (`customer_id`))');
        $this->addSql('CREATE TABLE `customers` (`id` INT UNSIGNED AUTO_INCREMENT NOT NULL, `name` VARCHAR(120) NOT NULL, `email` VARCHAR(255) NOT NULL, `status` VARCHAR(32) NOT NULL, `registered_at` VARCHAR(255), `updated_at` VARCHAR(255), `deleted_at` VARCHAR(255), `address_id` INT UNSIGNED, PRIMARY KEY (`id`), UNIQUE INDEX `uniq_customers_email` (`email`), INDEX `idx_customers_status_registered_at` (`status`, `registered_at`), CONSTRAINT `fk_customers_address_id` FOREIGN KEY (`address_id`) REFERENCES `customer_addresses`(`id`))');
        $this->addSql('CREATE TABLE `customer_audit_entries` (`id` INT UNSIGNED AUTO_INCREMENT NOT NULL, `customer_id` INT, `action` VARCHAR(64) NOT NULL, `message` VARCHAR(255) NOT NULL, `created_at` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`), INDEX `idx_customer_audit_entries_customer_id` (`customer_id`))');
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE `customer_audit_entries`');
        $this->addSql('DROP TABLE `customers`');
        $this->addSql('DROP TABLE `customer_addresses`');
    }
}
