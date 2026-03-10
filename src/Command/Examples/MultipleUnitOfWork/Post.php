<?php

namespace App\Command\Examples\MultipleUnitOfWork;

use App\Entity\User;
use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\ManyToOne;

#[Entity(tableName: 'example_posts')]
class Post
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(maxLength: 255)]
    public string $title;

    #[Property]
    public string $content;

    #[Property(name: 'created_at')]
    public string $createdAt;

    #[ManyToOne(targetEntity: User::class)]
    public User $author;
}
