<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 */
interface Storage
{
    public function setup(): void;

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $incomingMessageId
     */
    public function beginTransaction(string $endpoint, string $incomingMessageId): Transaction;

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $incomingMessageId
     */
    public function findOutbox(string $endpoint, string $incomingMessageId): ?Outbox;

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $incomingMessageId
     */
    public function markOutboxDispatched(string $endpoint, string $incomingMessageId): void;
}
