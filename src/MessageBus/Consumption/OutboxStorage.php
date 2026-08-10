<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption;

/**
 * Stores outbox records for one endpoint.
 *
 * Each endpoint must use its own outbox namespace, for example a separate table.
 * Sharing one storage between endpoints can incorrectly deduplicate published
 * events, because the same event message id is delivered to multiple endpoints
 * by design.
 *
 * @api
 *
 * @template-contravariant Tx of object
 */
interface OutboxStorage
{
    public function setup(): void;

    /**
     * @param non-empty-string $messageId
     * @return Outbox|null null when no outbox record exists for the message id
     */
    public function find(string $messageId): ?Outbox;

    /**
     * Atomically records the consumption if it is not recorded yet.
     *
     * @param non-empty-string $messageId
     * @return bool true if this call recorded it, false if a record already existed
     */
    public function store(string $messageId, Outbox $record): bool;

    /**
     * Atomically records the consumption if it is not recorded yet, within the given transaction.
     *
     * @param Tx $transaction
     * @param non-empty-string $messageId
     * @return bool true if this call recorded it, false if a record already existed
     */
    public function storeInTransaction(object $transaction, string $messageId, Outbox $record): bool;

    /**
     * Marks the record's outgoing messages as dispatched so they are not sent again.
     *
     * @param non-empty-string $messageId
     */
    public function markDispatched(string $messageId): void;
}
