<?php

declare(strict_types=1);

namespace App\Migrations;

use Articulate\Modules\Migrations\Generator\BaseMigration;

class Migration20260703000500Analytics extends BaseMigration
{
    protected function up(): void
    {
        $this->addSql('ALTER TABLE `products` ADD `analytics_family` VARCHAR(80)');
        $this->addSql('ALTER TABLE `orders` ADD `analytics_channel` VARCHAR(40)');
        $this->addSql('ALTER TABLE `order_items` ADD `margin_amount` DOUBLE');
    }

    protected function down(): void
    {
        $this->addSql('ALTER TABLE `order_items` DROP `margin_amount`');
        $this->addSql('ALTER TABLE `orders` DROP `analytics_channel`');
        $this->addSql('ALTER TABLE `products` DROP `analytics_family`');
    }
}
