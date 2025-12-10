<?php

namespace App\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\ManyToOne;

#[Entity(tableName: 'phones')]
class BrokenPhone
{
    #[PrimaryKey]
    #[AutoIncrement]
    #[Property]
    public int $id;

    #[Property(name: 'number', maxLength: 32, nullable: true)]
    public ?string $number;

    #[Property(name: 'label', maxLength: 32, nullable: true)]
    public ?string $label;

    #[Property(maxLength: 64)]
    public string $status;

    #[ManyToOne(targetEntity: User::class, referencedBy: 'brokenPhones', nullable: true)]
    public ?User $user;
}

