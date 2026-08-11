<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption;

/**
 * Stores handled message ids.
 *
 * Message ids are deduplicated within the endpoint scope because the same
 * published event id can be delivered to multiple endpoints by design.
 *
 * @api
 *
 * @template-contravariant Tx of object
 */
interface Deduplicator
{
    public function setup(): void;

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $messageId
     */
    public function isHandled(string $endpoint, string $messageId): bool;

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $messageId
     */
    public function markHandled(string $endpoint, string $messageId): bool;

    /**
     * @param Tx $transaction
     * @param non-empty-string $endpoint
     * @param non-empty-string $messageId
     */
    public function markHandledInTransaction(object $transaction, string $endpoint, string $messageId): bool;

    /**
     * Deletes deduplication records handled before the given time.
     *
     * @return int number of deleted records
     */
    public function purgeHandledBefore(\DateTimeImmutable $handledBefore): int;
}
