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
     * @var TTransaction
     * @phpstan-ignore generics.variance
     */
    public object $transaction { get; }

    /**
     * @param non-empty-list<Outbox> $outboxes
     * @throws OutboxAlreadyExists
     * @throws TransactionClosed
     */
    public function recordOutboxes(array $outboxes): void;

    /**
     * @throws TransactionClosed
     */
    public function commitIfBegun(): void;

    /**
     * @throws TransactionClosed
     */
    public function rollbackIfBegun(): void;
}
