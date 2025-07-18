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
     * @phpstan-ignore generics.variance
     */
    public object $wrappedTransaction { get; }

    /**
     * @throws OutboxAlreadyExists
     * @throws TransactionClosed
     */
    public function recordOutbox(Outbox $outbox): void;

    /**
     * @throws TransactionClosed
     */
    public function commit(): void;

    /**
     * @throws TransactionClosed
     */
    public function rollback(): void;
}
