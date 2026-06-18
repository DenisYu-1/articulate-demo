<?php

namespace App\Feature\Orders\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\Index;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\ManyToOne;

#[Entity(tableName: 'order_items')]
#[Index(['orderId'], name: 'idx_order_items_order_id')]
#[Index(['productId'], name: 'idx_order_items_product_id')]
final class OrderItem
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(name: 'order_id', nullable: false)]
    public ?string $orderId = null;

    #[Property(name: 'product_id')]
    public int $productId;

    #[Property]
    public int $quantity;

    #[Property(name: 'unit_price')]
    public float $unitPrice;

    #[ManyToOne(targetEntity: Order::class, referencedBy: 'items', column: 'order_id', nullable: false)]
    public ?Order $order = null;
}
