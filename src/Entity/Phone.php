<?php

namespace App\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\ManyToOne;

#[Entity(tableName: 'phones')]
class Phone
{
    #[PrimaryKey]
    #[AutoIncrement]
    #[Property]
    public int $id;

    #[Property(maxLength: 32)]
    public string $number;

    #[Property(maxLength: 32)]
    public string $label;

    #[ManyToOne(targetEntity: User::class, referencedBy: 'phones')]
    public User $user;
}

