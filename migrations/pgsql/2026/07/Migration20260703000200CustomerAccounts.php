<?php

declare(strict_types=1);

namespace App\Migrations;

use Articulate\Modules\Migrations\Generator\BaseMigration;

class Migration20260703000200CustomerAccounts extends BaseMigration
{
    protected function up(): void
    {
        $this->addSql('CREATE TABLE "customer_addresses" ("id" INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL, "street" VARCHAR(160) NOT NULL, "city" VARCHAR(80) NOT NULL, "state" VARCHAR(80), "postal_code" VARCHAR(32) NOT NULL, "country" VARCHAR(80) NOT NULL, "customer_id" INTEGER, PRIMARY KEY ("id"))');
        $this->addSql('CREATE INDEX "idx_customer_addresses_customer_id" ON "customer_addresses" ("customer_id")');
        $this->addSql('CREATE TABLE "customers" ("id" INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL, "name" VARCHAR(120) NOT NULL, "email" VARCHAR(255) NOT NULL, "status" VARCHAR(32) NOT NULL, "registered_at" VARCHAR(255), "updated_at" VARCHAR(255), "deleted_at" VARCHAR(255), "address_id" INTEGER, PRIMARY KEY ("id"), CONSTRAINT "fk_customers_address_id" FOREIGN KEY ("address_id") REFERENCES "customer_addresses"("id"))');
        $this->addSql('CREATE UNIQUE INDEX "uniq_customers_email" ON "customers" ("email")');
        $this->addSql('CREATE INDEX "idx_customers_status_registered_at" ON "customers" ("status", "registered_at")');
        $this->addSql('CREATE TABLE "customer_audit_entries" ("id" INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL, "customer_id" INTEGER, "action" VARCHAR(64) NOT NULL, "message" VARCHAR(255) NOT NULL, "created_at" VARCHAR(255) NOT NULL, PRIMARY KEY ("id"))');
        $this->addSql('CREATE INDEX "idx_customer_audit_entries_customer_id" ON "customer_audit_entries" ("customer_id")');
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE "customer_audit_entries"');
        $this->addSql('DROP TABLE "customers"');
        $this->addSql('DROP TABLE "customer_addresses"');
    }
}
