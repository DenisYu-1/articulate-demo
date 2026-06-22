<?php

namespace App\Features\CustomerAccounts\Entity;

use App\Features\CustomerAccounts\Repository\CustomerRepository;
use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\Index;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Lifecycle\PostLoad;
use Articulate\Attributes\Lifecycle\PostPersist;
use Articulate\Attributes\Lifecycle\PostRemove;
use Articulate\Attributes\Lifecycle\PostUpdate;
use Articulate\Attributes\Lifecycle\PrePersist;
use Articulate\Attributes\Lifecycle\PreRemove;
use Articulate\Attributes\Lifecycle\PreUpdate;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\OneToOne;
use Articulate\Attributes\SoftDeleteable;

#[Entity(tableName: 'customers', repositoryClass: CustomerRepository::class)]
#[SoftDeleteable(fieldName: 'deleted_at', columnName: 'deleted_at')]
#[Index(['email'], unique: true, name: 'uniq_customers_email')]
#[Index(['status', 'registered_at'], name: 'idx_customers_status_registered_at')]
class Customer
{
    /** @var \Closure(self):void|null */
    private static ?\Closure $auditWriter = null;

    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(maxLength: 120)]
    public string $name;

    #[Property(maxLength: 255)]
    public string $email;

    #[Property(maxLength: 32)]
    public string $status = 'active';

    #[Property(name: 'registered_at', nullable: true)]
    public ?string $registered_at = null;

    #[Property(name: 'updated_at', nullable: true)]
    public ?string $updated_at = null;

    #[Property(name: 'deleted_at', nullable: true)]
    public ?string $deleted_at = null;

    #[OneToOne(targetEntity: Address::class, column: 'address_id')]
    public ?Address $address = null;

    /** @var string[] */
    public array $callbacksCalled = [];

    public static function setAuditWriter(?\Closure $auditWriter): void
    {
        self::$auditWriter = $auditWriter;
    }

    #[PrePersist]
    public function onPrePersist(): void
    {
        $this->record('PrePersist');

        if ($this->status === 'reject-pre-persist') {
            throw new \RuntimeException('Customer rejected by PrePersist callback');
        }

        $this->registered_at ??= self::now();
    }

    #[PostPersist]
    public function onPostPersist(): void
    {
        $this->record('PostPersist');

        if (self::$auditWriter !== null) {
            (self::$auditWriter)($this);
        }
    }

    #[PreUpdate]
    public function onPreUpdate(): void
    {
        $this->record('PreUpdate');
        $this->updated_at = self::now();
    }

    #[PostUpdate]
    public function onPostUpdate(): void
    {
        $this->record('PostUpdate');
    }

    #[PreRemove]
    public function onPreRemove(): void
    {
        $this->record('PreRemove');
        $this->markDeleted();
    }

    #[PostRemove]
    public function onPostRemove(): void
    {
        $this->record('PostRemove');
    }

    #[PostLoad]
    public function onPostLoad(): void
    {
        $this->record('PostLoad');
    }

    public function markDeleted(): void
    {
        $this->status = 'deleted';
        $this->deleted_at ??= self::now();
    }

    public function reactivate(): void
    {
        $this->status = 'active';
        $this->deleted_at = null;
    }

    private function record(string $callback): void
    {
        $this->callbacksCalled[] = $callback;
    }

    private static function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }
}
