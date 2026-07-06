<?php

namespace App\Features\Tagging\Command;

use App\Features\CustomerAccounts\Entity\Customer;
use App\Features\Orders\Command\OrdersCommandSupport;
use App\Features\Tagging\Entity\Tag;
use App\Features\Tagging\Entity\TaggableCustomer;
use App\Features\Tagging\Entity\TaggableOrder;
use Articulate\Attributes\Relations\MorphTypeRegistry;
use Articulate\Modules\EntityManager\Collection;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:tagging:demo', description: 'Polymorphic tagging demo')]
final class TaggingDemoCommand extends Command
{
    use OrdersCommandSupport;

    public function __construct(
        private readonly EntityManager $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $suffix = bin2hex(random_bytes(4));

        $io->section('Polymorphic tagging');

        Customer::setAuditWriter(null);
        $this->registerMorphTypes();

        $customer = $this->createCustomer($suffix, 'Tagging Customer');
        $blankCustomer = $this->createCustomer($suffix, 'Tagging Blank Customer');
        $fallbackCustomer = $this->createCustomer($suffix, 'Tagging Fallback Customer');
        $fixture = $this->createProductWithStock($suffix, 'TAGGING', 6, 79.00);

        $order = $this->createOrder($customer);
        $this->scheduleOrderGraph($order, [
            $this->createOrderItem($order, $fixture['product'], 1),
        ]);
        $this->entityManager->flush();

        $orderId = $order->id;
        if ($orderId === null) {
            throw new \RuntimeException('Order id was not assigned by flush().');
        }

        $this->entityManager->clear();

        $urgent = $this->createTag('Urgent', "urgent-{$suffix}");
        $vip = $this->createTag('VIP', "vip-{$suffix}");
        $fallback = $this->createTag('Fallback Type', "fallback-type-{$suffix}");
        $this->entityManager->persist($urgent);
        $this->entityManager->persist($vip);
        $this->entityManager->persist($fallback);
        $this->entityManager->flush();

        $taggedOrder = $this->entityManager->find(TaggableOrder::class, $orderId);
        $taggedCustomer = $this->entityManager->find(TaggableCustomer::class, $customer->id);
        if (!$taggedOrder instanceof TaggableOrder || !$taggedCustomer instanceof TaggableCustomer) {
            throw new \RuntimeException('Taggable projections were not found.');
        }

        if (!$taggedOrder->tags instanceof Collection || !$taggedCustomer->tags instanceof Collection) {
            throw new \RuntimeException('MorphToMany relations were not hydrated as collections.');
        }

        $taggedOrder->tags->add($urgent);
        $taggedCustomer->tags->add($urgent);
        $taggedCustomer->tags->add($vip);
        $this->entityManager->flush();

        $orderTags = $this->loadTagsFor(TaggableOrder::class, $orderId);
        $customerTags = $this->loadTagsFor(TaggableCustomer::class, (string) $customer->id);
        $ordersForUrgent = $this->loadOrdersForTag($urgent->id);
        $blankCustomerTags = $this->loadTagsFor(TaggableCustomer::class, (string) $blankCustomer->id);

        $io->text(sprintf(
            'Order %s tags: %s',
            $taggedOrder instanceof TaggableOrder ? $taggedOrder->id : $orderId,
            $this->tagNames($orderTags),
        ));
        $io->text(sprintf(
            'Customer #%d tags: %s',
            $taggedCustomer instanceof TaggableCustomer ? $taggedCustomer->id : $customer->id,
            $this->tagNames($customerTags),
        ));
        $io->text(sprintf('Orders tagged urgent: %d', count($ordersForUrgent)));
        $io->text(sprintf('Blank customer tags: %d', count($blankCustomerTags)));

        $nativeLoad = $taggedOrder instanceof TaggableOrder
            ? $this->entityManager->loadRelation($taggedOrder, 'tags')
            : null;
        $io->text('Current ORM loadRelation(TaggableOrder::tags): ' . get_debug_type($nativeLoad));

        MorphTypeRegistry::clear();
        $fallbackType = MorphTypeRegistry::getAlias(TaggableCustomer::class);
        $this->insertTaggable($fallback->id, $fallbackType, (string) $fallbackCustomer->id);
        $this->registerMorphTypes();

        $io->text('Registered alias type: ' . MorphTypeRegistry::getAlias(TaggableCustomer::class));
        $io->text('Unregistered fallback type: ' . $fallbackType);
        $io->text('Fallback tag pivot types: ' . implode(', ', $this->pivotTypesForTag($fallback->id)));

        $io->success('Tagging demo completed');

        return Command::SUCCESS;
    }

    private function registerMorphTypes(): void
    {
        MorphTypeRegistry::clear();
        MorphTypeRegistry::register(TaggableOrder::class, 'order');
        MorphTypeRegistry::register(TaggableCustomer::class, 'customer');
    }

    private function createTag(string $name, string $slug): Tag
    {
        $tag = new Tag();
        $tag->name = $name;
        $tag->slug = $slug;

        return $tag;
    }

    private function insertTaggable(int $tagId, string $type, string $id): void
    {
        $this->entityManager->getConnection()->executeQuery(
            'INSERT INTO taggables (tag_id, taggable_type, taggable_id) VALUES (?, ?, ?)',
            [$tagId, $type, $id],
        );
    }

    /**
     * @return Tag[]
     */
    private function loadTagsFor(string $entityClass, string $entityId): array
    {
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select('tag_id')
            ->from('taggables')
            ->where('taggable_type', MorphTypeRegistry::getAlias($entityClass))
            ->where('taggable_id', $entityId)
            ->orderBy('tag_id', 'ASC')
            ->getResult();

        $tagIds = array_map('intval', array_column($rows, 'tag_id'));
        if ($tagIds === []) {
            return [];
        }

        return $this->entityManager
            ->createQueryBuilder(Tag::class)
            ->whereIn('id', $tagIds)
            ->orderBy('id', 'ASC')
            ->getResult(Tag::class);
    }

    /**
     * @return TaggableOrder[]
     */
    private function loadOrdersForTag(int $tagId): array
    {
        $orderIds = $this->loadTaggableIdsForTag($tagId, MorphTypeRegistry::getAlias(TaggableOrder::class));
        if ($orderIds === []) {
            return [];
        }

        return $this->entityManager
            ->createQueryBuilder(TaggableOrder::class)
            ->whereIn('id', $orderIds)
            ->orderBy('id', 'ASC')
            ->getResult(TaggableOrder::class);
    }

    /**
     * @return string[]
     */
    private function loadTaggableIdsForTag(int $tagId, string $type): array
    {
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select('taggable_id')
            ->from('taggables')
            ->where('tag_id', $tagId)
            ->where('taggable_type', $type)
            ->orderBy('taggable_id', 'ASC')
            ->getResult();

        return array_map('strval', array_column($rows, 'taggable_id'));
    }

    /**
     * @return string[]
     */
    private function pivotTypesForTag(int $tagId): array
    {
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select('taggable_type')
            ->from('taggables')
            ->where('tag_id', $tagId)
            ->orderBy('taggable_type', 'ASC')
            ->getResult();

        return array_map('strval', array_column($rows, 'taggable_type'));
    }

    /**
     * @param Tag[] $tags
     */
    private function tagNames(array $tags): string
    {
        if ($tags === []) {
            return '(none)';
        }

        return implode(', ', array_map(static fn (Tag $tag): string => $tag->name, $tags));
    }
}
