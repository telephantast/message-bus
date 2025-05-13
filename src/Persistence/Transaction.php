<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 * @template-covariant TWrappedTransaction of object = object
 */
interface Transaction
{
    /**
     * @var TWrappedTransaction
     */
    public ?object $wrappedTransaction { get; }

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
