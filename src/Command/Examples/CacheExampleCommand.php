<?php

namespace App\Command\Examples;

use App\Entity\User;
use Articulate\Connection;
use Articulate\Modules\EntityManager\EntityManager;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:example:cache',
    description: 'Demonstrates second-level cache and statement cache',
)]
final class CacheExampleCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityManager $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $this->entityManager->transactional(function (EntityManager $em) {
            $user = new User();
            $user->name     = 'Cache Demo User';
            $user->email    = 'cache-' . uniqid() . '@example.com';
            $user->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
            $user->status   = 'active';
            $em->persist($user);
            $em->flush();
            return $user->id;
        });

        // ----------------------------------------------------------------
        // 1. SECOND-LEVEL CACHE
        //
        // Caches raw entity row data keyed by class + id behind a PSR-6 pool.
        // find() reads through it; flush() evicts the affected row automatically.
        // Survives EntityManager::clear() — unlike the identity map which is
        // process-local and lost on clear().
        // ----------------------------------------------------------------
        $io->section('Second-level cache');

        $pool     = new ArrayAdapter();
        $cachedEm = new EntityManager($this->connection, secondLevelCache: $pool);

        $t = microtime(true);
        $cachedEm->find(User::class, $userId);
        $coldMs = round((microtime(true) - $t) * 1000, 2);

        // clear() drops the identity map but NOT the second-level cache.
        $cachedEm->clear();

        $t = microtime(true);
        $cachedEm->find(User::class, $userId);
        $warmMs = round((microtime(true) - $t) * 1000, 2);

        $io->definitionList(
            ['find() cold (identity map empty, pool empty)' => "{$coldMs} ms — hit database, row written to pool"],
            ['find() warm (identity map cleared, pool hit)' => "{$warmMs} ms — served from pool, no DB query"],
        );

        // flush() evicts the row from the pool automatically.
        $user = $cachedEm->find(User::class, $userId);
        $user->status = 'suspended';
        $cachedEm->persist($user);
        $cachedEm->flush();
        $cachedEm->clear();

        $t = microtime(true);
        $cachedEm->find(User::class, $userId);
        $afterFlushMs = round((microtime(true) - $t) * 1000, 2);

        $io->text("find() after flush()+clear() — {$afterFlushMs} ms — flush() evicted cache entry, hit database");
        $io->success('Second-level cache: cold → warm → evicted by flush() → cold');

        // ----------------------------------------------------------------
        // 2. CROSS-CONTEXT STALENESS
        //
        // flush() evicts from the writing EM's pool only. A second EM backed
        // by a different pool has no knowledge of the write and returns stale
        // data until TTL expires. This is by design — the ORM does not
        // coordinate eviction across pool instances.
        // ----------------------------------------------------------------
        $io->section('Cross-context staleness (gotcha)');

        $pool1 = new ArrayAdapter();
        $pool2 = new ArrayAdapter();
        $em1   = new EntityManager($this->connection, secondLevelCache: $pool1);
        $em2   = new EntityManager($this->connection, secondLevelCache: $pool2);

        // Both contexts warm their own pools independently.
        $em1->find(User::class, $userId);
        $em2->find(User::class, $userId);

        // em1 writes — evicts from pool1. pool2 is unaffected.
        $userViaEm1 = $em1->find(User::class, $userId);
        $userViaEm1->status = 'deleted';
        $em1->persist($userViaEm1);
        $em1->flush();
        $em1->clear();

        $em2->clear(); // drop identity map; pool2 still has the pre-write row
        $staleUser = $em2->find(User::class, $userId);

        $io->text("em1 wrote status='deleted'");
        $io->text("em2 reads from its own pool: status='{$staleUser->status}' (stale)");
        $io->warning(
            'Cross-context staleness is by design. '
            . 'Keep secondLevelCacheTtl short for entities shared across EM instances, '
            . 'or evict explicitly via $pool->deleteItem($key) in the writing context.'
        );

        // ----------------------------------------------------------------
        // 3. STATEMENT CACHE
        //
        // Caches the compiled SQL string for a given query structure.
        // On a cache hit the ORM skips PHP-side SQL compilation and jumps
        // straight to parameter binding. Saves CPU — not DB round-trips.
        // Pays off in high-throughput services where the same query shape
        // runs hundreds of times per second.
        // ----------------------------------------------------------------
        $io->section('Statement cache');

        $stmtPool  = new ArrayAdapter();
        $noStmtEm  = new EntityManager($this->connection); // no statement cache
        $stmtEm    = new EntityManager($this->connection, statementCache: $stmtPool);

        // getSQL() triggers build() — exactly where statement cache sits — without
        // hitting the DB, so we measure pure PHP compilation cost.
        $buildQuery = static fn (EntityManager $em) => $em
            ->createQueryBuilder(User::class)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->getSQL();

        $iterations = 2000;

        // Warm the statement cache with one call before measuring.
        $buildQuery($stmtEm);

        $t = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $buildQuery($noStmtEm);
        }
        $noStmtMs = round((microtime(true) - $t) * 1000, 2);

        $t = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $buildQuery($stmtEm);
        }
        $stmtMs = round((microtime(true) - $t) * 1000, 2);

        $io->definitionList(
            ["{$iterations}x getSQL() without statement cache" => "{$noStmtMs} ms (SQL compiled from scratch each time)"],
            ["{$iterations}x getSQL() with statement cache"    => "{$stmtMs} ms (SQL returned from pool after first call)"],
        );
        $io->note('No DB queries fired — pure PHP compilation cost. Real-world benefit is in high-throughput services running the same query shape hundreds of times per second.');
        $io->success('Statement cache: SQL compiled once per unique query shape, reused on subsequent calls');

        $this->entityManager->transactional(function (EntityManager $em) use ($userId) {
            $user = $em->find(User::class, $userId);
            $em->remove($user);
            $em->flush();
        });

        return Command::SUCCESS;
    }
}
