<?php

namespace App\Features\CustomerAccounts\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\SoftDeleteable;

#[Entity(tableName: 'customers')]
#[SoftDeleteable(fieldName: 'deleted_at', columnName: 'deleted_at')]
final class CustomerSummary
{
    #[PrimaryKey]
    public int $id;

    #[Property(maxLength: 120)]
    public string $name;

    #[Property(maxLength: 255)]
    public string $email;
}
