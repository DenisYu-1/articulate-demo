<?php

namespace App\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Lifecycle\PostPersist;
use Articulate\Attributes\Lifecycle\PrePersist;
use Articulate\Attributes\Property;

#[Entity(tableName: 'audit_logs')]
class AuditLog
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(maxLength: 64)]
    public string $action;

    #[Property]
    public string $created_at;

    #[PrePersist]
    public function onPrePersist(): void
    {
        if (!isset($this->created_at) || $this->created_at === '') {
            $this->created_at = (new \DateTime())->format('Y-m-d H:i:s');
        }
    }

    #[PostPersist]
    public function onPostPersist(): void
    {
    }
}
