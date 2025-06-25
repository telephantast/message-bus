<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 * @template-covariant TTransaction of object
 */
interface Transaction
{
    /**
     * @var TTransaction
     */
    public object $wrappedTransaction { get; } /** @phpstan-ignore generics.variance */

    /**
     * @throws TransactionClosed
     */
    public function commit(): void;

    /**
     * @throws TransactionClosed
     */
    public function rollback(): void;
}
