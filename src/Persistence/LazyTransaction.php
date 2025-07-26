<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 * @template-covariant TTransaction of object
 */
interface LazyTransaction
{
    /**
     * This property must begin transaction.
     *
     * @var TTransaction
     * @phpstan-ignore generics.variance
     */
    public object $transaction { get; }

    /**
     * This method must begin transaction.
     *
     * @param non-empty-list<Outbox> $outboxes
     * @throws OutboxAlreadyExists
     * @throws TransactionClosed
     */
    public function recordOutboxes(array $outboxes): void;

    /**
     * This method must not begin transaction.
     *
     * @throws TransactionClosed
     */
    public function commitIfBegun(): void;

    /**
     * This method must not begin transaction.
     *
     * @throws TransactionClosed
     */
    public function rollbackIfBegun(): void;
}
