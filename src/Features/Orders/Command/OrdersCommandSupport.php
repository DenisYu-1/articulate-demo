<?php

namespace App\Features\Orders\Command;

use App\Features\Catalog\Entity\Category;
use App\Features\Catalog\Entity\Product;
use App\Features\Catalog\Entity\ProductStatus;
use App\Features\CustomerAccounts\Entity\Customer;
use App\Features\Orders\Entity\Order;
use App\Features\Orders\Entity\OrderItem;
use App\Features\Orders\Entity\StockLock;

trait OrdersCommandSupport
{
    private function createCustomer(string $suffix, string $label = 'Orders Customer'): Customer
    {
        $customer = new Customer();
        $customer->name = $label;
        $customer->email = sprintf('orders-%s-%s@example.test', strtolower(str_replace(' ', '-', $label)), $suffix);
        $customer->status = 'active';

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }

    /**
     * @return array{category: Category, product: Product, stock: StockLock}
     */
    private function createProductWithStock(string $suffix, string $skuPrefix, int $stock, float $price): array
    {
        $category = new Category();
        $category->name = "Orders Gear {$suffix}";
        $category->slug = "orders-gear-{$skuPrefix}-{$suffix}";
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $product = new Product();
        $product->sku = "{$skuPrefix}-{$suffix}";
        $product->name = "Orders Product {$skuPrefix}";
        $product->slug = strtolower("orders-product-{$skuPrefix}-{$suffix}");
        $product->description = null;
        $product->setStatus(ProductStatus::Active);
        $product->categoryId = $category->id;
        $product->price = $price;
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $stockLock = new StockLock();
        $stockLock->productId = $product->id;
        $stockLock->stock = $stock;
        $this->upsertStock($stockLock->productId, $stockLock->stock);

        return ['category' => $category, 'product' => $product, 'stock' => $stockLock];
    }

    private function createOrder(Customer $customer, string $status = 'open'): Order
    {
        $order = new Order();
        $order->customer = $customer;
        $order->status = $status;

        if ($status === 'shipped') {
            $order->shippedAt = self::now();
        }

        return $order;
    }

    private function createOrderItem(Order $order, Product $product, int $quantity): OrderItem
    {
        $item = new OrderItem();
        $item->order = $order;
        $item->productId = $product->id;
        $item->quantity = $quantity;
        $item->unitPrice = $product->price;

        return $item;
    }

    /**
     * @param OrderItem[] $items
     */
    private function scheduleOrderGraph(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $this->entityManager->persist($item);
        }

        $this->entityManager->persist($order);
    }

    private function lockStock(int $productId): StockLock
    {
        $stock = $this->entityManager
            ->createQueryBuilder(StockLock::class)
            ->where('product_id', $productId)
            ->lock()
            ->getSingleResult();

        if (!$stock instanceof StockLock) {
            throw new \RuntimeException("Stock row not found for product {$productId}");
        }

        return $stock;
    }

    private function upsertStock(int $productId, int $stock): void
    {
        $connection = $this->entityManager->getConnection();
        $sql = $connection->getDriverName() === 'pgsql'
            ? 'INSERT INTO product_stock (product_id, stock) VALUES (?, ?) ON CONFLICT (product_id) DO UPDATE SET stock = EXCLUDED.stock'
            : 'INSERT INTO product_stock (product_id, stock) VALUES (?, ?) ON DUPLICATE KEY UPDATE stock = VALUES(stock)';

        $connection->executeQuery($sql, [$productId, $stock]);
    }

    private function shortError(\Throwable $e): string
    {
        $message = preg_replace('/\s+/', ' ', $e->getMessage());

        return mb_strimwidth($message ?? '', 0, 180, '...');
    }

    private static function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }
}
