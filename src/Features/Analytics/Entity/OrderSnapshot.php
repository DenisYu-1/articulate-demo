<?php

namespace App\Features\Analytics\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;

#[Entity(tableName: 'orders', readOnly: true)]
final class OrderSnapshot
{
    #[PrimaryKey]
    #[Property(maxLength: 36)]
    public string $id;

    #[Property(maxLength: 32)]
    public string $status;

    #[Property(name: 'placed_at')]
    public string $placedAt;

    #[Property(name: 'analytics_channel', nullable: true, maxLength: 40)]
    public ?string $analyticsChannel = null;
}
