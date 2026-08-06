<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Processing;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface OutboxStorage
{
    /**
     * @param non-empty-string $endpoint
     */
    public function setup(string $endpoint): void;

    /**
     * @return Outbox|null null when no outbox record exists for the processing id
     */
    public function find(ProcessingId $id): ?Outbox;

    /**
     * Atomically records the consumption if it is not recorded yet.
     *
     * @return bool true if this call recorded it, false if a record already existed
     */
    public function store(ProcessingId $id, Outbox $record): bool;

    /**
     * Atomically records the consumption if it is not recorded yet, within the given transaction.
     *
     * @param Tx $transaction
     * @return bool true if this call recorded it, false if a record already existed
     */
    public function storeInTransaction(object $transaction, ProcessingId $id, Outbox $record): bool;

    /**
     * Marks the record's outgoing messages as dispatched so they are not sent again.
     */
    public function markDispatched(ProcessingId $id): void;
}
