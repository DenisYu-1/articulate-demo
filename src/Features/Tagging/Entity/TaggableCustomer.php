<?php

namespace App\Features\Tagging\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Relations\MorphToMany;
use Articulate\Modules\EntityManager\Collection;

#[Entity(tableName: 'customers')]
final class TaggableCustomer
{
    #[PrimaryKey]
    public int $id;

    #[MorphToMany(targetEntity: Tag::class, name: 'taggable', targetIdColumn: 'tag_id')]
    public array|Collection $tags = [];
}
