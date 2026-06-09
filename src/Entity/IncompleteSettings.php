<?php

namespace App\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;

/**
 * Intentionally incomplete mapping of the settings_demo table.
 *
 * The table has a third column — group_name VARCHAR(100) NOT NULL with no default —
 * that is deliberately absent here. This demonstrates two failure modes:
 *
 *  1. Runtime: INSERT fails with "Field 'group_name' doesn't have a default value"
 *  2. Dev-time: `articulate:validate` warns about the unmapped required column
 *
 * @see app:example:incomplete-entity
 */
#[Entity(tableName: 'settings_demo')]
class IncompleteSettings
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(name: 'setting_key', maxLength: 255)]
    public string $settingKey;
}
