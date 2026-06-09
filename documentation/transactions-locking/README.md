# Transactions and Locking

Manage transactions and row-level locking.

**Runnable example:** [Transactions and Locking](../../examples/transactions-locking/README.md)

## Transactional

```php
$entityManager->transactional(function (EntityManager $em) {
    $em->persist($entity);
    $em->flush();
    return $entity;
});
```

## Manual Control

- `beginTransaction()` – start
- `commit()` – flush and commit
- `rollback()` – rollback

## Locking

Use `lock()` on the query builder for `SELECT ... FOR UPDATE`. Requires an active transaction.
