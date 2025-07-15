<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 */
interface Storage
{
    public function setup(): void;

    public function beginTransaction(): Transaction;

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
    public function markOutboxSent(string $endpoint, string $incomingMessageId): void;
}
