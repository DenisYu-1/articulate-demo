<?php

namespace App\Feature\Catalog\Entity;

use App\Feature\Catalog\Type\ProductStatusConverter;
use Articulate\Attributes\Entity;
use Articulate\Attributes\Indexes\AutoIncrement;
use Articulate\Attributes\Indexes\Index;
use Articulate\Attributes\Indexes\PrimaryKey;
use Articulate\Attributes\Property;
use Articulate\Attributes\Relations\ManyToMany;
use Articulate\Modules\EntityManager\Collection;

#[Entity(tableName: 'products')]
#[Index(['sku'], unique: true, name: 'uniq_products_sku')]
#[Index(['categoryId', 'status'], name: 'idx_products_category_status')]
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

    #[Property(name: 'category_id', nullable: true)]
    public ?int $categoryId = null;

    #[Property]
    public float $price;

    #[ManyToMany(targetEntity: Category::class, referencedBy: 'products')]
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
