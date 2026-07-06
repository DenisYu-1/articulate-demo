<?php

namespace App\Features\BulkImport\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;

#[Entity(tableName: 'products')]
final class ImportProduct
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(maxLength: 64)]
    public string $sku;

    #[Property(name: 'product_name', maxLength: 160)]
    public string $name;

    #[Property(maxLength: 160)]
    public string $slug;

    #[Property(name: 'category_id', nullable: true)]
    public ?int $categoryId = null;

    #[Property(maxLength: 32)]
    public string $status;

    #[Property]
    public float $price;
}
