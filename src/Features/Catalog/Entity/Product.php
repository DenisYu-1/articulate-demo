<?php

namespace App\Features\Catalog\Entity;

use App\Features\Catalog\Type\ProductStatusConverter;
use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\Index;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\ManyToMany;
use Articulate\Attributes\Relations\MappingTable;
use Articulate\Attributes\Relations\MappingTableProperty;
use Articulate\Modules\EntityManager\Collection;

#[Entity(tableName: 'products')]
#[Index(['sku'], unique: true, name: 'uniq_products_sku', concurrent: true)]
#[Index(['categoryId', 'status'], name: 'idx_products_category_status', concurrent: true)]
final class Product
{
    #[PrimaryKey]
    #[AutoIncrement]
    public ?int $id = null;

    #[Property(maxLength: 64)]
    public string $sku;

    #[Property(name: 'product_name', maxLength: 160)]
    public string $name;

    #[Property(maxLength: 160, nullable: false)]
    public ?string $slug = null;

    #[Property(maxLength: 500, nullable: true)]
    public ?string $description = null;

    #[Property(maxLength: 32)]
    public string $status;

    #[Property(nullable: true)]
    public ?int $categoryId = null;

    #[Property]
    public float $price;

    #[ManyToMany(
        targetEntity: Category::class,
        referencedBy: 'products',
        mappingTable: new MappingTable(
            name: 'categories_products',
            properties: [
                new MappingTableProperty('is_primary', 'int', defaultValue: '0'),
                new MappingTableProperty('position', 'int', defaultValue: '0'),
                new MappingTableProperty('assigned_at', 'datetime', createdAt: true),
                new MappingTableProperty('updated_at', 'datetime', updatedAt: true),
            ],
        ),
    )]
    public array|Collection $categories = [];

    public function setStatus(ProductStatus $status): void
    {
        $this->status = (new ProductStatusConverter())->convertToDatabase($status);
    }

    public function statusEnum(): ProductStatus
    {
        return (new ProductStatusConverter())->convertToPHP($this->status);
    }
}
