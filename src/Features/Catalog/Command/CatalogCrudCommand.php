<?php

namespace App\Features\Catalog\Command;

use App\Features\Catalog\Entity\Category;
use App\Features\Catalog\Entity\InventorySlot;
use App\Features\Catalog\Entity\Product;
use App\Features\Catalog\Entity\ProductStatus;
use Articulate\Modules\EntityManager\EntityManager;
use PDOException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:catalog:crud', description: 'Catalog CRUD and relation demo')]
final class CatalogCrudCommand extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $suffix = bin2hex(random_bytes(4));

        $io->section('Catalog CRUD');

        $primaryCategory = $this->createCategory("Cameras {$suffix}", "cameras-{$suffix}");
        $secondaryCategory = $this->createCategory("Travel Gear {$suffix}", "travel-gear-{$suffix}");

        $this->entityManager->persist($primaryCategory);
        $this->entityManager->persist($secondaryCategory);
        $this->entityManager->flush();

        $primaryCategory = $this->findCategoryBySlug($primaryCategory->slug);
        $secondaryCategory = $this->findCategoryBySlug($secondaryCategory->slug);

        $product = $this->createProduct(
            sku: "CAM-{$suffix}",
            name: 'Mirrorless Camera Kit',
            slug: "mirrorless-camera-kit-{$suffix}",
            status: ProductStatus::Active,
            categoryId: $primaryCategory->id,
            price: 1299.00,
        );

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $product = $this->findProductBySku($product->sku);
        $this->attachCategory($product->id, $primaryCategory->id);
        $this->attachCategory($product->id, $secondaryCategory->id);
        $this->upsertStock($product->id, 25);

        $stock = $this->entityManager->find(InventorySlot::class, $product->id);
        $categories = $this->loadCategoriesForProduct($product->id);

        $io->success(sprintf(
            'Created product #%d (%s), status=%s, stock=%d, categories=%d',
            $product->id,
            $product->sku,
            $product->statusEnum()->value,
            $stock instanceof InventorySlot ? $stock->stock : 0,
            is_countable($categories) ? count($categories) : 0,
        ));

        $product->price = 1199.00;
        $product->description = 'Discounted bundle price';
        $this->entityManager->flush();
        $this->entityManager->clear();

        $updated = $this->findProductBySku($product->sku);
        $io->success(sprintf('Updated price for %s to %.2f', $updated->sku, $updated->price));

        $deleted = $this->createProduct(
            sku: "DELETE-{$suffix}",
            name: 'Temporary Catalog Item',
            slug: "temporary-catalog-item-{$suffix}",
            status: ProductStatus::Draft,
            categoryId: $primaryCategory->id,
            price: 10.00,
        );
        $this->entityManager->persist($deleted);
        $this->entityManager->flush();
        $deleted = $this->findProductBySku($deleted->sku);
        $this->entityManager->remove($deleted);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->success('Deleted temporary product');

        $this->demonstrateMissingRequiredValue($io, $primaryCategory->id, $suffix);
        $this->demonstrateDuplicateSku($io, $updated->sku, $primaryCategory->id, $suffix);

        return Command::SUCCESS;
    }

    private function createCategory(string $name, string $slug): Category
    {
        $category = new Category();
        $category->name = $name;
        $category->slug = $slug;

        return $category;
    }

    private function createProduct(
        string $sku,
        string $name,
        string $slug,
        ProductStatus $status,
        ?int $categoryId,
        float $price,
    ): Product {
        $product = new Product();
        $product->sku = $sku;
        $product->name = $name;
        $product->slug = $slug;
        $product->description = null;
        $product->setStatus($status);
        $product->categoryId = $categoryId;
        $product->price = $price;

        return $product;
    }

    private function findCategoryBySlug(string $slug): Category
    {
        $category = $this->entityManager
            ->createQueryBuilder(Category::class)
            ->where('slug', $slug)
            ->getSingleResult();

        if (!$category instanceof Category) {
            throw new \RuntimeException("Category not found: {$slug}");
        }

        return $category;
    }

    private function findProductBySku(string $sku): Product
    {
        $product = $this->entityManager
            ->createQueryBuilder(Product::class)
            ->where('sku', $sku)
            ->getSingleResult();

        if (!$product instanceof Product) {
            throw new \RuntimeException("Product not found: {$sku}");
        }

        return $product;
    }

    private function attachCategory(int $productId, int $categoryId): void
    {
        $this->entityManager->getConnection()->executeQuery(
            'INSERT INTO categories_products (products_id, categories_id) VALUES (?, ?)',
            [$productId, $categoryId],
        );
    }

    /**
     * The current library release defines ManyToMany metadata, but its loader calls
     * a missing ReflectionManyToMany method. Query the conventional pivot directly.
     *
     * @return Category[]
     */
    private function loadCategoriesForProduct(int $productId): array
    {
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select('categories_id')
            ->from('categories_products')
            ->where('products_id', $productId)
            ->getResult();

        $categoryIds = array_column($rows, 'categories_id');
        if ($categoryIds === []) {
            return [];
        }

        return $this->entityManager
            ->createQueryBuilder(Category::class)
            ->whereIn('id', $categoryIds)
            ->orderBy('id', 'ASC')
            ->getResult();
    }

    private function upsertStock(int $productId, int $stock): void
    {
        $connection = $this->entityManager->getConnection();
        $sql = $connection->getDriverName() === 'pgsql'
            ? 'INSERT INTO product_stock (product_id, stock) VALUES (?, ?) ON CONFLICT (product_id) DO UPDATE SET stock = EXCLUDED.stock'
            : 'INSERT INTO product_stock (product_id, stock) VALUES (?, ?) ON DUPLICATE KEY UPDATE stock = VALUES(stock)';

        $connection->executeQuery($sql, [$productId, $stock]);
    }

    private function demonstrateMissingRequiredValue(SymfonyStyle $io, int $categoryId, string $suffix): void
    {
        try {
            $this->entityManager->transactional(function (EntityManager $em) use ($categoryId, $suffix): void {
                $product = $this->createProduct(
                    sku: "BROKEN-{$suffix}",
                    name: 'Broken Product',
                    slug: '',
                    status: ProductStatus::Draft,
                    categoryId: $categoryId,
                    price: 1.00,
                );
                $product->slug = null;

                $em->persist($product);
                $em->flush();
            });

            $io->error('Expected NOT NULL/default constraint failure, but insert succeeded');
        } catch (PDOException $e) {
            $this->entityManager->clear();
            $io->success('Missing required slug rejected: ' . $this->shortError($e));
        }
    }

    private function demonstrateDuplicateSku(SymfonyStyle $io, string $sku, int $categoryId, string $suffix): void
    {
        try {
            $this->entityManager->transactional(function (EntityManager $em) use ($sku, $categoryId, $suffix): void {
                $duplicate = $this->createProduct(
                    sku: $sku,
                    name: 'Duplicate SKU Product',
                    slug: "duplicate-sku-product-{$suffix}",
                    status: ProductStatus::Draft,
                    categoryId: $categoryId,
                    price: 2.00,
                );

                $em->persist($duplicate);
                $em->flush();
            });

            $io->error('Expected unique constraint failure, but insert succeeded');
        } catch (PDOException $e) {
            $this->entityManager->clear();
            $io->success('Duplicate SKU rejected: ' . $this->shortError($e));
        }
    }

    private function shortError(PDOException $e): string
    {
        $message = preg_replace('/\s+/', ' ', $e->getMessage());

        return mb_strimwidth($message ?? '', 0, 180, '...');
    }
}
