<?php

namespace App\Entity;

use Articulate\Attributes\Entity;
use Articulate\Modules\EntityManager\Collection;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\Index;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\ManyToMany;
use Articulate\Attributes\Relations\OneToMany;
use Articulate\Attributes\Relations\OneToOne;

#[Entity(tableName: 'users')]
#[Index(['email'], unique: true, concurrent: true)] // Non-blocking index creation
#[Index(['created_at', 'status'], concurrent: false)]
class User
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(maxLength: 120)]
    public string $name;

    #[Property(maxLength: 255)]
    public string $email;

    #[Property(name: 'created_at')]
    public string $createdAt;

    #[Property]
    public string $status;

    #[OneToMany(ownedBy: 'user', targetEntity: Phone::class)]
    public array|Collection $phones = [];

    #[OneToMany(ownedBy: 'user', targetEntity: BrokenPhone::class)]
    public array|Collection $brokenPhones = [];

    #[ManyToMany(targetEntity: Group::class, referencedBy: 'users')]
    public array|Collection $groups = [];

    #[OneToOne(targetEntity: Cart::class, referencedBy: 'user')]
    public ?Cart $cart = null;
}
