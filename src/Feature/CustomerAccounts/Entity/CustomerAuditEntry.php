<?php

namespace App\Feature\CustomerAccounts\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\Index;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;

#[Entity(tableName: 'customer_audit_entries')]
#[Index(['customer_id'], name: 'idx_customer_audit_entries_customer_id')]
final class CustomerAuditEntry
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(name: 'customer_id', nullable: true)]
    public ?int $customer_id = null;

    #[Property(maxLength: 64)]
    public string $action;

    #[Property(maxLength: 255)]
    public string $message;

    #[Property(name: 'created_at')]
    public string $created_at;
}
