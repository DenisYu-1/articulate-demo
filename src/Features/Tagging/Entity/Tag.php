<?php

namespace App\Features\Tagging\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\Index;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\MorphedByMany;
use Articulate\Modules\EntityManager\Collection;

#[Entity(tableName: 'tags')]
#[Index(['slug'], unique: true, name: 'uniq_tags_slug')]
final class Tag
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(maxLength: 80)]
    public string $name;

    #[Property(maxLength: 120)]
    public string $slug;

    #[MorphedByMany(targetEntity: TaggableOrder::class, name: 'taggable', targetIdColumn: 'tag_id')]
    public array|Collection $orders = [];

    #[MorphedByMany(targetEntity: TaggableCustomer::class, name: 'taggable', targetIdColumn: 'tag_id')]
    public array|Collection $customers = [];
}
