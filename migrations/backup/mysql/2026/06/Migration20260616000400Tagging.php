<?php

declare(strict_types=1);

namespace App\Migrations;

use Articulate\Modules\Migrations\Generator\BaseMigration;

final class Migration20260616000400Tagging extends BaseMigration
{
    protected function up(): void
    {
        $this->addSql(
            'CREATE TABLE tags (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(80) NOT NULL,
                slug VARCHAR(120) NOT NULL,
                CONSTRAINT uniq_tags_slug UNIQUE (slug)
            )'
        );

        $this->addSql(
            'CREATE TABLE taggables (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                tag_id INT UNSIGNED NOT NULL,
                taggable_type VARCHAR(255) NOT NULL,
                taggable_id VARCHAR(36) NOT NULL,
                PRIMARY KEY (taggable_type, taggable_id, tag_id),
                UNIQUE KEY uniq_taggables_id (id),
                INDEX idx_taggables_tag_id (tag_id),
                INDEX idx_taggables_taggable (taggable_type, taggable_id),
                CONSTRAINT fk_taggables_tags_tag_id
                    FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE
            )'
        );
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE IF EXISTS taggables');
        $this->addSql('DROP TABLE IF EXISTS tags');
    }
}
