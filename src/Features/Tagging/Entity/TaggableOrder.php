<?php

namespace App\Features\Tagging\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\MorphToMany;
use Articulate\Modules\EntityManager\Collection;

#[Entity(tableName: 'orders')]
final class TaggableOrder
{
    #[PrimaryKey]
    #[Property(maxLength: 36)]
    public string $id;

    #[MorphToMany(targetEntity: Tag::class, name: 'taggable', targetIdColumn: 'tag_id')]
    public array|Collection $tags = [];
}
