<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Outbox;

use Thesis\MessageBus\Persistence\Storage;

/**
 * @api
 * @template-covariant TTransaction of object
 * @extends Storage<TTransaction>
 */
interface OutboxStorage extends Storage
{
    /**
     * @return OutboxTransaction<TTransaction>
     */
    public function beginTransaction(): OutboxTransaction;

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $incomingMessageId
     */
    public function findOutbox(string $endpoint, string $incomingMessageId): ?Outbox;

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $incomingMessageId
     * @throws OutboxDoesNotExist
     */
    public function completeOutbox(string $endpoint, string $incomingMessageId): void;
}
