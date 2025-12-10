<?php

namespace App\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\ManyToMany;

#[Entity(tableName: 'groups')]
class Group
{
    #[PrimaryKey]
    #[AutoIncrement]
    #[Property]
    public int $id;

    #[Property(maxLength: 120)]
    public string $name;

    #[ManyToMany(ownedBy: 'groups', targetEntity: User::class)]
    public array $users;
}

