<?php

namespace App\Feature\CustomerAccounts\Entity;

use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\Index;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Lifecycle\PostLoad;
use Articulate\Attributes\Property;

#[Entity(tableName: 'customer_addresses')]
#[Index(['customer_id'], name: 'idx_customer_addresses_customer_id')]
class Address
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(maxLength: 160)]
    public string $street;

    #[Property(maxLength: 80)]
    public string $city;

    #[Property(maxLength: 80, nullable: true)]
    public ?string $state = null;

    #[Property(name: 'postal_code', maxLength: 32)]
    public string $postal_code;

    #[Property(maxLength: 80)]
    public string $country;

    #[Property(name: 'customer_id', nullable: true)]
    public ?int $customer_id = null;

    /** @var string[] */
    public array $callbacksCalled = [];

    #[PostLoad]
    public function onPostLoad(): void
    {
        $this->callbacksCalled[] = 'PostLoad';
    }
}
