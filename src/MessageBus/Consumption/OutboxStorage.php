<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption;

/**
 * Stores outbox records.
 *
 * Message ids are deduplicated within the endpoint scope because the same
 * published event id can be delivered to multiple endpoints by design.
 *
 * @api
 *
 * @template-contravariant Tx of object
 */
interface OutboxStorage
{
    public function setup(): void;

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $messageId
     * @return Outbox|null null when no outbox record exists for the endpoint/message pair
     */
    public function find(string $endpoint, string $messageId): ?Outbox;

    /**
     * Atomically records the consumption if it is not recorded yet.
     *
     * @param non-empty-string $endpoint
     * @param non-empty-string $messageId
     * @return bool true if this call recorded it, false if a record already existed
     */
    public function store(string $endpoint, string $messageId, Outbox $record): bool;

    /**
     * Atomically records the consumption if it is not recorded yet, within the given transaction.
     *
     * @param Tx $transaction
     * @param non-empty-string $endpoint
     * @param non-empty-string $messageId
     * @return bool true if this call recorded it, false if a record already existed
     */
    public function storeInTransaction(object $transaction, string $endpoint, string $messageId, Outbox $record): bool;

    /**
     * Marks the record's outgoing messages as dispatched so they are not sent again.
     *
     * @param non-empty-string $endpoint
     * @param non-empty-string $messageId
     */
    public function markDispatched(string $endpoint, string $messageId): void;
}
