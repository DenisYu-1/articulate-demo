<?php

namespace App\Features\Analytics\Command;

use App\Features\Analytics\Diagnostics\CountingQueryLogger;
use App\Features\Catalog\Entity\Category;
use App\Features\Catalog\Entity\Product;
use App\Features\Catalog\Entity\ProductStatus;
use App\Features\CustomerAccounts\Entity\Customer;
use App\Features\Orders\Entity\Order;
use App\Features\Orders\Entity\OrderItem;
use Articulate\Connection;
use Articulate\Modules\EntityManager\EntityManager;
use Articulate\Modules\Generators\UuidGenerator;
use Articulate\Modules\Repository\RepositoryFactory;
use Psr\Cache\CacheItemPoolInterface;

trait AnalyticsCommandSupport
{
    /**
     * @return array{
     *     suffix: string,
     *     orderIds: string[],
     *     productIds: int[],
     *     categoryIds: int[],
     *     firstItemId: int,
     *     from: \DateTimeImmutable,
     *     to: \DateTimeImmutable
     * }
     */
    private function seedAnalyticsDataset(int $orderCount = 5): array
    {
        $suffix = bin2hex(random_bytes(4));
        $base = new \DateTimeImmutable('-12 hours');
        $from = $base->modify('-1 hour');
        $to = $base->modify('+' . ($orderCount + 2) . ' hours');

        $primaryCategory = $this->createCategory("Analytics Cameras {$suffix}", "analytics-cameras-{$suffix}");
        $accessoryCategory = $this->createCategory("Analytics Accessories {$suffix}", "analytics-accessories-{$suffix}");

        $products = [
            $this->createProduct($suffix, 'ANA-CAM', 'Analytics Camera', $primaryCategory->id, 299.00),
            $this->createProduct($suffix, 'ANA-LENS', 'Analytics Lens', $primaryCategory->id, 899.00),
            $this->createProduct($suffix, 'ANA-BAG', 'Analytics Bag', $accessoryCategory->id, 79.50),
        ];

        $customer = new Customer();
        $customer->name = 'Analytics Customer';
        $customer->email = "analytics-{$suffix}@example.test";
        $customer->status = 'active';
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        $statuses = ['open', 'shipped', 'cancelled'];
        $orderIds = [];
        $firstItemId = null;

        for ($i = 0; $i < $orderCount; $i++) {
            $status = $statuses[$i % count($statuses)];
            $placedAt = $base->modify("+{$i} hours");

            $order = new Order();
            $order->customer = $customer;
            $order->status = $status;
            $order->placedAt = $placedAt->format('Y-m-d H:i:s');
            if ($status === 'shipped') {
                $order->shippedAt = $placedAt->modify('+2 hours')->format('Y-m-d H:i:s');
            }

            $items = [
                $this->createOrderItem($order, $products[$i % count($products)], ($i % 3) + 1),
                $this->createOrderItem($order, $products[($i + 1) % count($products)], 1),
            ];

            foreach ($items as $item) {
                $this->entityManager->persist($item);
            }

            $this->entityManager->persist($order);
            $order->id ??= (new UuidGenerator())->generate(Order::class);
            $orderIds[] = $order->id;
        }

        $this->entityManager->flush();

        $firstItemId = $this->firstOrderItemId($orderIds[0]);
        if ($firstItemId === null) {
            throw new \RuntimeException('Analytics seed did not create order items.');
        }

        $this->entityManager->clear();

        return [
            'suffix' => $suffix,
            'orderIds' => $orderIds,
            'productIds' => array_map(fn (Product $product): int => (int) $product->id, $products),
            'categoryIds' => [(int) $primaryCategory->id, (int) $accessoryCategory->id],
            'firstItemId' => $firstItemId,
            'from' => $from,
            'to' => $to,
        ];
    }

    private function createCategory(string $name, string $slug): Category
    {
        $category = new Category();
        $category->name = $name;
        $category->slug = $slug;
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createProduct(string $suffix, string $skuPrefix, string $name, int $categoryId, float $price): Product
    {
        $product = new Product();
        $product->sku = "{$skuPrefix}-{$suffix}";
        $product->name = $name;
        $product->slug = strtolower("{$skuPrefix}-{$suffix}");
        $product->description = null;
        $product->setStatus(ProductStatus::Active);
        $product->categoryId = $categoryId;
        $product->price = $price;
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    private function createOrderItem(Order $order, Product $product, int $quantity): OrderItem
    {
        $item = new OrderItem();
        $item->order = $order;
        $item->productId = (int) $product->id;
        $item->quantity = $quantity;
        $item->unitPrice = $product->price;

        return $item;
    }

    private function firstOrderItemId(string $orderId): ?int
    {
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select('id')
            ->from('order_items')
            ->where('order_id', $orderId)
            ->orderBy('id', 'ASC')
            ->limit(1)
            ->getResult();

        return isset($rows[0]['id']) ? (int) $rows[0]['id'] : null;
    }

    private function createInstrumentedEntityManager(
        ?CountingQueryLogger $logger = null,
        ?CacheItemPoolInterface $resultCache = null,
        ?CacheItemPoolInterface $secondLevelCache = null,
    ): EntityManager {
        $connection = new Connection(
            $this->env('DATABASE_DSN'),
            $this->env('DATABASE_USER'),
            $this->env('DATABASE_PASSWORD'),
            $logger,
        );

        $entityManager = new EntityManager(
            $connection,
            resultCache: $resultCache,
            secondLevelCache: $secondLevelCache,
        );
        $entityManager->setRepositoryFactory(new RepositoryFactory($entityManager));

        return $entityManager;
    }

    private function env(string $name): string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if (!is_string($value) || $value === '') {
            throw new \RuntimeException("Missing environment variable {$name}");
        }

        return preg_replace_callback(
            '/\$\{([A-Z0-9_]+)\}/',
            fn (array $matches): string => $this->env($matches[1]),
            $value,
        );
    }
}
