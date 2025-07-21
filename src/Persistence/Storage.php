<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\Endpoint;

/**
 * @api
 * @template-covariant TTransaction of object
 */
interface Storage
{
    public function setup(): void;

    /**
     * @param non-empty-string $incomingMessageId
     * @return Transaction<TTransaction>
     */
    public function beginTransaction(Endpoint $endpoint, string $incomingMessageId): Transaction;

    /**
     * @param non-empty-string $incomingMessageId
     */
    public function findOutbox(Endpoint $endpoint, string $incomingMessageId): ?Outbox;

    /**
     * @param non-empty-string $incomingMessageId
     */
    public function markOutboxDispatched(Endpoint $endpoint, string $incomingMessageId): void;
}
