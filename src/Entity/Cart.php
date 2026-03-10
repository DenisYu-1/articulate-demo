<?php

namespace App\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\OneToOne;

#[Entity(tableName: 'carts')]
class Cart
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(type: 'float')]
    public float $total;

    #[OneToOne(ownedBy: 'cart', targetEntity: User::class)]
    public User $user;
}

