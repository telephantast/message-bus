<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

/**
 * @api
 */
interface EntityFinder
{
    /**
     * @template TEntity of object
     * @param class-string<TEntity> $class
     * @param non-empty-array<non-empty-string, mixed> $criteria
     * @return ?TEntity
     */
    public function findBy(string $class, array $criteria): ?object;
}
