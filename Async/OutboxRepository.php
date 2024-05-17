<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

/**
 * @api
 */
interface OutboxRepository
{
    /**
     * @param non-empty-string $consumedMessageId
     */
    public function get(string $consumedMessageId): Outbox;
}
