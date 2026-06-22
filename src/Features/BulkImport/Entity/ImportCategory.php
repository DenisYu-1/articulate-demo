<?php

namespace App\Features\BulkImport\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;

#[Entity(tableName: 'categories')]
final class ImportCategory
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(maxLength: 120)]
    public string $name;

    #[Property(maxLength: 120)]
    public string $slug;
}
