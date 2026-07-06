<?php

namespace App\Features\Orders\Entity;

use App\Features\CustomerAccounts\Entity\Customer;
use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\Index;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Lifecycle\PrePersist;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\ManyToOne;
use Articulate\Attributes\Relations\OneToMany;
use Articulate\Modules\EntityManager\Collection;

#[Entity(tableName: 'orders')]
#[Index(['customer', 'status'], name: 'idx_orders_customer_status')]
#[Index(['status', 'placedAt'], name: 'idx_orders_status_placed_at')]
final class Order
{
    #[PrimaryKey(generator: PrimaryKey::GENERATOR_UUID_V4)]
    #[Property(maxLength: 36)]
    public ?string $id = null;

    #[Property(maxLength: 32)]
    public string $status = 'open';

    #[Property(name: 'placed_at', nullable: false)]
    public ?string $placedAt = null;

    #[Property(name: 'shipped_at', nullable: true)]
    public ?string $shippedAt = null;

    #[ManyToOne(targetEntity: Customer::class, column: 'customer_id', nullable: false)]
    public ?Customer $customer = null;

    #[OneToMany(ownedBy: 'order', targetEntity: OrderItem::class, lazy: true)]
    public array|Collection $items = [];

    #[PrePersist]
    public function stampPlacedAt(): void
    {
        $this->placedAt ??= (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }
}
