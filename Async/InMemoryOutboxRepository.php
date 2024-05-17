<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

/**
 * @api
 */
final class InMemoryOutboxRepository implements OutboxRepository
{
    public function get(string $consumedMessageId): Outbox
    {
        return new InMemoryOutbox();
    }
}
