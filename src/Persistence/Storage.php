<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 * @template-covariant TWrappedTransaction of object = object
 * @template-covariant TTransaction of Transaction<TWrappedTransaction> = Transaction<object>
 */
interface Storage
{
    /**
     * @return TTransaction
     */
    public function beginTransaction(): Transaction;

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $messageId
     */
    public function findOutbox(string $endpoint, string $messageId): ?Outbox;

    /**
     * @throws OutboxDoesNotExist
     */
    public function updateOutbox(Outbox $outbox): void;
}
