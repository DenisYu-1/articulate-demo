<?php

namespace App\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\Index;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\ManyToMany;
use Articulate\Attributes\Relations\OneToMany;
use Articulate\Attributes\Relations\OneToOne;

#[Entity]
#[Index(['email'], unique: true, concurrent: true)] // Non-blocking index creation
#[Index(['created_at', 'status'], concurrent: false)] // Regular index
class User
{
    #[PrimaryKey]
    #[AutoIncrement]
    #[Property]
    public int $id;

    #[Property(maxLength: 120)]
    public string $name;

    #[Property(maxLength: 255)]
    public string $email;

    #[Property]
    public DateTime $created_at;

    #[Property]
    public string $status;

    #[OneToMany(ownedBy: 'user', targetEntity: Phone::class)]
    public array $phones;

    #[OneToMany(ownedBy: 'user', targetEntity: BrokenPhone::class)]
    public array $brokenPhones;

    #[ManyToMany(targetEntity: Group::class, referencedBy: 'users')]
    public array $groups;

    #[OneToOne(targetEntity: Cart::class, referencedBy: 'user')]
    public Cart $cart;
}
