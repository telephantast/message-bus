<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface Deduplicator
{
    /**
     * @param non-empty-string $endpoint
     */
    public function setup(string $endpoint): void;

    public function isHandled(ProcessingId $id): bool;

    public function markHandled(ProcessingId $id): bool;

    /**
     * @param Tx $transaction
     */
    public function markHandledInTransaction(object $transaction, ProcessingId $id): bool;
}
