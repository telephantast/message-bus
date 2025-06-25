<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence\Outbox;

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
     * @param non-empty-string $incomingMessageId
     * @param non-empty-string $endpoint
     */
    public function findOutbox(string $incomingMessageId, string $endpoint): ?Outbox;

    /**
     * @throws OutboxDoesNotExist
     */
    public function updateOutbox(Outbox $outbox): void;
}
