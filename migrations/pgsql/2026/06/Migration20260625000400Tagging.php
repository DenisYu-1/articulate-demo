<?php

declare(strict_types=1);

namespace App\Migrations;

use Articulate\Modules\Migrations\Generator\BaseMigration;

class Migration20260625000400Tagging extends BaseMigration
{
    protected function up(): void
    {
        $this->addSql('CREATE TABLE "tags" ("id" INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL, "name" VARCHAR(80) NOT NULL, "slug" VARCHAR(120) NOT NULL, PRIMARY KEY ("id"))');
        $this->addSql('CREATE UNIQUE INDEX "uniq_tags_slug" ON "tags" ("slug")');
        $this->addSql('CREATE TABLE "taggables" ("id" INTEGER NOT NULL, "taggable_type" VARCHAR(255) NOT NULL, "taggable_id" VARCHAR(36) NOT NULL, "tag_id" INTEGER NOT NULL, PRIMARY KEY ("taggable_type", "taggable_id", "tag_id"), CONSTRAINT "fk_taggables_tag_id" FOREIGN KEY ("tag_id") REFERENCES "tags"("id"))');
        $this->addSql('CREATE INDEX "taggable_type_taggable_id_index" ON "taggables" ("taggable_type", "taggable_id")');
    }

    protected function down(): void
    {
        $this->addSql('DROP TABLE "taggables"');
        $this->addSql('DROP TABLE "tags"');
    }
}
