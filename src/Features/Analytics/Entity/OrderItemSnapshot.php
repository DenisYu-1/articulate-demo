<?php

namespace App\Features\Analytics\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;

#[Entity(tableName: 'order_items', readOnly: true)]
final class OrderItemSnapshot
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(name: 'product_id')]
    public int $productId;

    #[Property]
    public int $quantity;

    #[Property(name: 'unit_price')]
    public float $unitPrice;
}
