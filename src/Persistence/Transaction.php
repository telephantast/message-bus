<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 * @template-covariant TTransaction of object = object
 */
interface Transaction
{
    /**
     * @var TTransaction
     */
    public object $wrappedTransaction { get; } /** @phpstan-ignore generics.variance */

    /**
     * @throws OutboxAlreadyExists
     * @throws TransactionClosed
     */
    public function commit(Outbox $outbox): void;

    /**
     * @throws TransactionClosed
     */
    public function rollback(): void;
}
