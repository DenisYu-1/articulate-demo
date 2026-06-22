<?php

namespace App\Features\Analytics\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;

#[Entity(tableName: 'products', readOnly: true)]
final class ProductSnapshot
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(name: 'product_name', maxLength: 160)]
    public string $name;

    #[Property(name: 'category_id', nullable: true)]
    public ?int $categoryId = null;
}
