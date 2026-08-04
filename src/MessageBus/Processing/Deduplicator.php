<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Processing;

/**
 * @template-contravariant Tx of object
 */
interface Deduplicator
{
    public function isHandled(ProcessingId $id): bool;

    public function markHandled(ProcessingId $id): bool;

    /**
     * @param Tx $transaction
     */
    public function markHandledInTransaction(object $transaction, ProcessingId $id): bool;
}
