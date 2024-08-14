<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Outbox;

/**
 * @api
 */
interface OutboxStorage
{
    /**
     * @param ?non-empty-string $queue
     * @param non-empty-string $messageId
     */
    public function get(?string $queue, string $messageId): ?Outbox;

    /**
     * @param ?non-empty-string $queue
     * @param non-empty-string $messageId
     * @throws OutboxAlreadyExists
     */
    public function create(?string $queue, string $messageId, Outbox $outbox): void;

    /**
     * @param ?non-empty-string $queue
     * @param non-empty-string $messageId
     */
    public function empty(?string $queue, string $messageId): void;
}
