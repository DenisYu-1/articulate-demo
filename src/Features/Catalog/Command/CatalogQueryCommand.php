<?php

namespace App\Features\Catalog\Command;

use App\Features\Catalog\Entity\Category;
use App\Features\Catalog\Entity\Product;
use App\Features\Catalog\Entity\ProductStatus;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:catalog:query', description: 'Catalog query builder demo')]
final class CatalogQueryCommand extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $data = $this->seedCatalog();
        $products = $data['products'];
        $category = $data['category'];
        $skus = array_map(fn (Product $product): string => $product->sku, $products);

        $io->section('Catalog queries');

        $found = $this->entityManager->find(Product::class, $products[0]->id);
        $io->text(sprintf('find(Product, id): %s', $found instanceof Product ? $found->sku : 'not found'));

        $repo = $this->entityManager->getRepository(Product::class);
        $bySku = $repo->findBy(['sku' => $skus], ['id' => 'ASC']);
        $io->text('findBy(sku IN seeded): ' . count($bySku));

        $activeInCategory = $this->entityManager
            ->createQueryBuilder(Product::class)
            ->where('category_id', $category->id)
            ->where('status', ProductStatus::Active->value)
            ->orderBy('id', 'ASC')
            ->getResult();
        $io->text('where(category_id + status): ' . count($activeInCategory));

        $visible = $this->entityManager
            ->createQueryBuilder(Product::class)
            ->whereIn('sku', $skus)
            ->whereIn('status', [ProductStatus::Active->value, ProductStatus::Draft->value])
            ->orderBy('price', 'DESC')
            ->getResult();
        $io->text('whereIn(status active/draft) + orderBy(price DESC): ' . count($visible));

        $page = $this->entityManager
            ->createQueryBuilder(Product::class)
            ->whereIn('sku', $skus)
            ->orderBy('id', 'ASC')
            ->limit(2)
            ->offset(1)
            ->getResult();
        $io->text('offset pagination (limit=2, offset=1): ' . implode(', ', array_map(
            fn (Product $product): string => $product->sku,
            $page,
        )));

        $io->success('Loaded many-to-many categories for first product: ' . count($products[0]->categories));

        return Command::SUCCESS;
    }

    /**
     * @return array{category: Category, products: Product[]}
     */
    private function seedCatalog(): array
    {
        $suffix = bin2hex(random_bytes(4));

        $category = new Category();
        $category->name = "Catalog Query {$suffix}";
        $category->slug = "catalog-query-{$suffix}";

        $this->entityManager->persist($category);
        $this->entityManager->flush();
        $category = $this->findCategoryBySlug($category->slug);

        $seed = [
            ["QUERY-ACTIVE-{$suffix}", 'Trail Camera', ProductStatus::Active, 149.00],
            ["QUERY-DRAFT-{$suffix}", 'Unreleased Lens', ProductStatus::Draft, 899.00],
            ["QUERY-DISC-{$suffix}", 'Retired Tripod', ProductStatus::Discontinued, 79.00],
            ["QUERY-ACTIVE-PRO-{$suffix}", 'Pro Camera Body', ProductStatus::Active, 1899.00],
        ];

        foreach ($seed as [$sku, $name, $status, $price]) {
            $product = new Product();
            $product->sku = $sku;
            $product->name = $name;
            $product->slug = strtolower($sku);
            $product->description = null;
            $product->setStatus($status);
            $product->categoryId = $category->id;
            $product->price = $price;

            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $category = $this->findCategoryBySlug($category->slug);

        $products = [];
        foreach ($seed as [$sku]) {
            $product = $this->findProductBySku($sku);
            $product->categories->add($category);
            $products[] = $product;
        }
        $this->entityManager->flush();

        return ['category' => $category, 'products' => $products];
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

}
