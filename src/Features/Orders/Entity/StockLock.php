<?php

namespace App\Features\Orders\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;

#[Entity(tableName: 'product_stock')]
final class StockLock
{
    #[PrimaryKey]
    #[Property(name: 'product_id')]
    public int $productId;

    #[Property]
    public int $stock;
}
