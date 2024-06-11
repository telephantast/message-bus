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
     */
    public function save(?string $queue, string $messageId, Outbox $outbox): void;
}
