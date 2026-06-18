<?php

namespace App\Feature\Catalog\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\Index;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\ManyToMany;
use Articulate\Modules\EntityManager\Collection;

#[Entity(tableName: 'categories')]
#[Index(['slug'], unique: true, name: 'uniq_categories_slug')]
final class Category
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(maxLength: 120)]
    public string $name;

    #[Property(maxLength: 120)]
    public string $slug;

    #[ManyToMany(ownedBy: 'categories', targetEntity: Product::class)]
    public array|Collection $products = [];
}
