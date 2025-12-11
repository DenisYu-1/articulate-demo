<?php

namespace App\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;

#[Entity(tableName: 'user')]
class LoginUser
{
    #[PrimaryKey]
    #[Property]
    public int $id;

    #[Property(maxLength: 120)]
    public string $login;

    #[Property(maxLength: 120)]
    public string $password;
}

