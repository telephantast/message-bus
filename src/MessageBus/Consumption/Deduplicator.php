<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption;

/**
 * Stores handled message ids for one endpoint.
 *
 * Each endpoint must use its own deduplicator namespace, for example a separate
 * table. Sharing one deduplicator between endpoints can incorrectly deduplicate
 * published events, because the same event message id is delivered to multiple
 * endpoints by design.
 *
 * @api
 *
 * @template-contravariant Tx of object
 */
interface Deduplicator
{
    public function setup(): void;

    /**
     * @param non-empty-string $messageId
     */
    public function isHandled(string $messageId): bool;

    /**
     * @param non-empty-string $messageId
     */
    public function markHandled(string $messageId): bool;

    /**
     * @param Tx $transaction
     * @param non-empty-string $messageId
     */
    public function markHandledInTransaction(object $transaction, string $messageId): bool;
}
